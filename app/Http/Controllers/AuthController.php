<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\CreateRequest;
use App\Http\Requests\Auth\UpdateConfigRequest;
use App\Models\DestroyedUser;
use App\Models\RecipeDraft;
use App\Models\User as ModelsUser;
use Laravel\Socialite\Two\User;
use App\Traits\GeneralHelpers;
use Auth;
use Carbon\Carbon;
use Cookie;
use DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use GeneralHelpers;

    private $draftCtrl;

    public function __construct()
    {
        $this->draftCtrl = new RecipeDraftController();
    }

    public function authRedirect(Request $request, string $provider)
    {
        session()->put('redirect_url', $request->redirect);
        return Socialite::driver($provider)->with(["prompt" => "select_account"])->redirect();
    }

    public function authCallback(Request $request, $provider)
    {
        try {
            // Get user from socialite callback
            $user = Socialite::driver($provider)->stateless()->user();
            $userModel = $this->findOrCreateUser($user);

            // Generate token
            if (!$token = Auth::login($userModel)) {
                return $this->jsonResponse(false, null, "Wrong credentials.", null, 401);
            }
            session()->regenerate();

            // Update last login and geolocation info
            $geolocation = file_get_contents("https://api.ipgeolocation.io/ipgeo?apiKey=" . env("IPGEOLOCATION_KEY"));

            $configs = $userModel->configs;

            $userModel->configs = [...$configs, "last_login" => Carbon::now()];
            $userModel->geolocation = $geolocation ? json_decode($geolocation) : null;
            $userModel->save();

            // Create cookie
            $cookie = cookie(
                'login_session',
                $token,
                config('jwt.ttl'),
                '/',
                env("APP_DOMAIN") === "" ? null : env("APP_DOMAIN"),
            );

            return redirect()->away(env("APP_FE_URL") . session('redirect_url'))->withCookie($cookie);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function createToken(Request $request)
    {
        try {
            // Check user already registered or not
            $modelUser = $this->checkModel("User");

            if ($modelUser) {
                $user = $modelUser->where('email', $request->user["email"])->first();

                if (!$user) {
                    $user = $modelUser->create([
                        "email" => $request->user["email"],
                        "avatar_url" => $request->user["photo"]
                    ]);
                }
            }

            // Create token
            if (!$token = Auth::login($user)) {
                return $this->jsonResponse(false, null, "Wrong credentials.", null, 401);
            }
            session()->regenerate();

            // Update last login
            $configs = $user->configs;

            if ($configs) {
                $user->configs = [...$configs, "last_login" => Carbon::now()];
            } else {
                $user->configs = ["last_login" => Carbon::now()];
            }

            $user->save();

            return $this->jsonResponse(data: [
                "token" => $token,
                "user" => $user
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    private function findOrCreateUser(User $socialiteUser)
    {
        $modelUser = $this->checkModel("User");

        if ($modelUser) {
            $user = $modelUser->where('email', $socialiteUser->getEmail())->first();

            if (!$user) {
                $user = $modelUser->create([
                    "email" => $socialiteUser->getEmail(),
                    "avatar_url" => $socialiteUser->getAvatar(),
                    "username" => $socialiteUser->getNickname(),
                    "socialite_data" => $socialiteUser,
                ]);
            }

            return $user;
        }

        return null;
    }

    public function create(CreateRequest $request)
    {
        try {
            $user = ModelsUser::where("email", $request->email)->first();

            if (!$user) {
                return $this->jsonResponse(false, null, "User with email {$request->email} is not exist.", null, 400);
            }
            if (!$token = Auth::login($user)) {
                return $this->jsonResponse(false, null, "Wrong credentials.", null, 401);
            }

            $cookie = cookie(
                'login_session',
                $token,
                config('jwt.ttl'),
                '/',
                env("APP_DOMAIN") === "" ? null : env("APP_DOMAIN"),
            );

            $payload = Auth::payload();

            $results = [
                "user"  => Auth::user(),
                // "token_expired_at" => Carbon::createFromTimestamp($payload('exp'))->format('Y-m-d\TH:i:s.u\Z'),
            ];

            return $this->jsonResponse(
                data: $results,
                cookieData: $cookie
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function me()
    {
        try {
            $results = [
                "user" => Auth::user()->makeHidden(["socialite_data", "geolocation"]),
                "token_expired_at" => Carbon::createFromTimestamp(JWTAuth::parseToken()->getClaim("exp"))->format('Y-m-d\TH:i:s.u\Z'),
            ];

            Log::info(print_r("CALLED", true));

            return $this->jsonResponse(
                data: $results,
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function logout()
    {
        try {
            Auth::logout();

            // $cookie = Cookie::forget("login_session");
            $removeToken = JWTAuth::invalidate(JWTAuth::getToken());

            if ($removeToken) {
                return $this->jsonResponse(message: "You are logged out.");
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function updateConfig(UpdateConfigRequest $request)
    {
        try {
            // Format config
            $configs = ModelsUser::select("configs")->where("id", Auth::id())->first()->configs;

            if ($request->has("hide_email")) {
                $configs["hide_email"] = filter_var($request->hide_email, FILTER_VALIDATE_BOOLEAN);
            }

            if ($request->has("push_subscription")) {
                $configs["push_subscription"] = $request->push_subscription;
            }

            DB::beginTransaction();

            // Update user
            ModelsUser::where("id", Auth::id())->update([
                "configs" => $configs
            ]);

            DB::commit();

            return $this->jsonResponse(data: ModelsUser::where("id", Auth::id())->first());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function deleteAccount()
    {
        try {
            DB::beginTransaction();

            // Force delete user
            ModelsUser::where("id", Auth::id())->forceDelete();

            // Add to destroyed user
            DestroyedUser::insert([
                "user_id" => Auth::id(),
                "created_at" => now(),
                "updated_at" => now()
            ]);

            // Remove user drafts
            $drafts = RecipeDraft::where("created_by", Auth::id())->get();
            if (count($drafts) > 0) {
                foreach ($drafts as $draft) {
                    $this->draftCtrl->delete($draft->id);
                }
            }

            DB::commit();

            // Logout user
            return $this->logout();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
}
