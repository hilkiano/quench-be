<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\CreateRequest;
use App\Models\User as ModelsUser;
use Laravel\Socialite\Two\User;
use App\Traits\GeneralHelpers;
use Auth;
use Carbon\Carbon;
use Cookie;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use GeneralHelpers;

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
            $userModel->configs = [
                "last_login" => Carbon::now()
            ];
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
                "token_expired_at" => Carbon::createFromTimestamp($payload('exp'))->format('Y-m-d\TH:i:s.u\Z'),
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

            $cookie = Cookie::forget("login_session");
            $removeToken = JWTAuth::invalidate(JWTAuth::getToken());

            if ($removeToken) {
                return $this->jsonResponse(message: "You are logged out.", cookieData: $cookie);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
}
