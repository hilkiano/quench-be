<?php

namespace App\Http\Controllers;

use App\Enums\RecipeStatus;
use App\Http\Requests\CrudRequest;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Requests\Recipe\AddToBookRequest;
use App\Http\Requests\Recipe\CreateRequest;
use App\Http\Requests\Recipe\MyRecipeRequest;
use App\Http\Requests\Recipe\RecipeBookRequest;
use App\Http\Requests\Recipe\SetPrivacyRequest;
use App\Http\Requests\Recipe\UpdateRequest;
use App\Http\Requests\Recipe\UpdateStatusRequest;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\UserRecipe;
use App\Traits\GeneralHelpers;
use Auth;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Request;

class RecipeController extends Controller
{
    use GeneralHelpers;

    private $crudController;
    private $dataController;
    private $notificationController;

    public function __construct()
    {
        $this->crudController = new CrudController();
        $this->dataController = new DataController();
        $this->notificationController = new NotificationController();
    }

    public function create(CreateRequest $request)
    {
        try {
            DB::beginTransaction();
            // Create recipe
            $recipeRequest = new CrudRequest();
            $recipeRequest->replace(
                [
                    "payload" => [
                        "title" => $request->title,
                        "description" => $request->has("description") ? $request->description : null,
                        "youtube_url" => $request->has("youtube_url") ? $request->youtube_url : null,
                        "method_id" => $request->method_id,
                        "image_url" => $this->storeImage("s3", $request->file("image"), "recipes"),
                        "status" => RecipeStatus::APPROVED->value,
                        "created_by" => Auth::id(),
                        "updated_by" => Auth::id()
                    ]
                ]
            );
            $createRecipe = $this->crudController->create($recipeRequest, "Recipe");

            if (json_decode($createRecipe->getContent())->status) {
                // Create steps
                $steps = json_decode($request->steps);
                foreach ($steps as $step) {
                    $stepRequest = new CrudRequest();
                    $stepRequest->replace(
                        [
                            "payload" => [
                                "recipe_id" => json_decode($createRecipe->getContent())->data->id,
                                "order" => $step->order,
                                "step" => $step->step,
                                "timer_seconds" => property_exists($step, "timer_seconds") ? $step->timer_seconds : null,
                                "video_starts_at" => property_exists($step, "video_starts_at") ? $step->video_starts_at : null,
                                "video_stops_at" => property_exists($step, "video_stops_at") ? $step->video_stops_at : null,
                                "created_by" => Auth::id(),
                                "updated_by" => Auth::id()
                            ]
                        ]
                    );
                    $this->crudController->create($stepRequest, "RecipeStep");
                }

                // Create ingredients
                $ingredients = json_decode($request->ingredients);
                foreach ($ingredients as $ingredient) {
                    $ingredientRequest = new CrudRequest();
                    $ingredientRequest->replace(
                        [
                            "payload" => [
                                "recipe_id" => json_decode($createRecipe->getContent())->data->id,
                                "name" => $ingredient->name,
                                "quantity" => $ingredient->quantity,
                                "unit_id" => $ingredient->unit,
                                "created_by" => Auth::id(),
                                "updated_by" => Auth::id()
                            ]
                        ]
                    );
                    $this->crudController->create($ingredientRequest, "RecipeIngredient");
                }

                // Create meta
                $metadataRequest = new CrudRequest();
                $metadataRequest->replace([
                    "payload" => [
                        "recipe_id" => json_decode($createRecipe->getContent())->data->id
                    ]
                ]);
                $this->crudController->create($metadataRequest, "RecipeMetadata");
            }

            DB::commit();

            $result = $this->dataController->index("Recipe", json_decode($createRecipe->getContent())->data->id, "steps&ingredients");
            return $this->jsonResponse(data: json_decode($result->getContent())->data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function update(UpdateRequest $request)
    {
        try {
            DB::beginTransaction();

            $recipeRequest = new CrudRequest();
            $updatePayload = [
                "id" => $request->id,
                "title" => $request->title,
                "description" => $request->has("description") ? $request->description : null,
                "youtube_url" => $request->has("youtube_url") ? $request->youtube_url : null,
                "method_id" => $request->method_id,
                "status" => RecipeStatus::APPROVED->value,
                "created_by" => Auth::id(),
                "updated_by" => Auth::id()
            ];

            // Delete old image from storage
            if ($request->has("image")) {
                $parsedUrl = parse_url(Recipe::withTrashed()->select("image_url")->find($request->id)->image_url);
                $this->deleteFile("s3", $parsedUrl["path"]);

                $updatePayload["image_url"] = $this->storeImage("s3", $request->file("image"), "recipes");
            }

            $recipeRequest->replace(
                [
                    "payload" => $updatePayload
                ]
            );

            $updateRecipe = $this->crudController->update($recipeRequest, "Recipe");
            if (json_decode($updateRecipe->getContent())->status) {
                // Update steps
                $ss = RecipeStep::where("recipe_id", $request->id)->get();
                foreach ($ss as $s) {
                    $steps = json_decode($request->steps);
                    foreach ($steps as $step) {
                    }
                }
                $steps = json_decode($request->steps);
                $stepIds = [];
                foreach ($steps as $step) {
                    $stepRequest = new CrudRequest();
                    $stepPayload = [
                        "recipe_id" => $request->id,
                        "order" => $step->order,
                        "step" => $step->step,
                        "timer_seconds" => property_exists($step, "timer_seconds") ? $step->timer_seconds : null,
                        "video_starts_at" => property_exists($step, "video_starts_at") ? $step->video_starts_at : null,
                        "video_stops_at" => property_exists($step, "video_stops_at") ? $step->video_stops_at : null,
                        "created_by" => Auth::id(),
                        "updated_by" => Auth::id()
                    ];
                    if (property_exists($step, "id")) {
                        $stepPayload["id"] = $step->id;
                        $stepRequest->replace(
                            [
                                "payload" => $stepPayload
                            ]
                        );

                        $this->crudController->update($stepRequest, "RecipeStep");
                        array_push($stepIds, $step->id);
                    } else {
                        $stepRequest->replace(
                            [
                                "payload" => $stepPayload
                            ]
                        );
                        $newStep = $this->crudController->create($stepRequest, "RecipeStep");
                        array_push($stepIds, json_decode($newStep->getContent())->data->id);
                    }
                }

                // Handle deleted steps
                RecipeStep::where("recipe_id", $request->id)->whereNotIn("id", $stepIds)->delete();

                // Update ingredients
                $ingredients = json_decode($request->ingredients);
                $ingredientIds = [];
                foreach ($ingredients as $ingredient) {
                    $ingredientRequest = new CrudRequest();
                    $ingredientPayload = [
                        "recipe_id" => $request->id,
                        "name" => $ingredient->name,
                        "quantity" => $ingredient->quantity,
                        "unit_id" => $ingredient->unit,
                        "created_by" => Auth::id(),
                        "updated_by" => Auth::id()
                    ];
                    if (property_exists($ingredient, "id")) {
                        $ingredientPayload["id"] = $ingredient->id;
                        $ingredientRequest->replace(
                            [
                                "payload" => $ingredientPayload
                            ]
                        );

                        $this->crudController->update($ingredientRequest, "RecipeIngredient");
                        array_push($ingredientIds, $ingredient->id);
                    } else {
                        $ingredientRequest->replace(
                            [
                                "payload" => $ingredientPayload
                            ]
                        );
                        $newIngredient = $this->crudController->create($ingredientRequest, "RecipeIngredient");
                        array_push($ingredientIds, json_decode($newIngredient->getContent())->data->id);
                    }
                }
                // Handle deleted ingredients
                RecipeIngredient::where("recipe_id", $request->id)->whereNotIn("id", $ingredientIds)->delete();
            }

            DB::commit();

            $result = $this->dataController->index("Recipe", $request->id, "steps&ingredients");
            return $this->jsonResponse(data: json_decode($result->getContent())->data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function delete(string $id)
    {
        try {
            $recipe = Recipe::where("id", $id)->first();

            // Delete image
            $imageUrl = parse_url($recipe->image_url);
            $this->deleteFile("s3", $imageUrl["path"]);

            $recipe->forceDelete();

            return $this->jsonResponse(true, $recipe);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function setPrivacy(SetPrivacyRequest $request)
    {
        try {
            $recipe = Recipe::find($request->id);

            $configs = $recipe->configs;
            if (array_key_exists("is_private", $recipe->configs)) {
                $configs["is_private"] = $request->is_private;
            }

            $recipe->configs = $configs;

            $recipe->save();

            return $this->jsonResponse(data: $request->all());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function updateStatus(UpdateStatusRequest $request)
    {
        try {
            $recipe = Recipe::find($request->id);

            DB::beginTransaction();

            $recipe->status = $request->status;
            $recipe->reason = $request->status !== RecipeStatus::REJECTED->value ? null : $request->reason;

            if ($request->status === RecipeStatus::APPROVED->value) {
                $recipe->approved_at = now();
                $recipe->approved_by = $request->approved_by;

                // Send notification if user has push subscription
                // $user = User::where("id", $recipe->created_by)->first();
                // if ($user) {
                //     $pushSubscription = $user->configs["push_subscription"];

                //     if ($pushSubscription) {
                //         $notificationRequest = new SendNotificationRequest();
                //         $notificationRequest->replace([
                //             "subscription" => $pushSubscription,
                //             "title" => "Congratulations!",
                //             "body" => "Recipe " . $recipe->title . " has been published. Check it now!",
                //             "url" => env("APP_FE_URL") . "/recipe/{$recipe->id}",
                //             "image" => $recipe->image_url
                //         ]);

                //         $this->notificationController->sendNotification($notificationRequest);
                //     }
                // }
            }

            $recipe->save();

            DB::commit();

            return $this->jsonResponse(data: $recipe);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function addToBook(AddToBookRequest $request)
    {
        try {
            $recipe = Recipe::with(["steps", "ingredients"])->find($request->id);

            $copyCount = Recipe::where("original_recipe_id", $request->id)->count();
            $count = $copyCount === 0 ? "" : " " . (string) $copyCount;

            // Create recipe
            $recipeRequest = new CrudRequest();
            $recipeRequest->replace(
                [
                    "payload" => [
                        "title" => "[Copy{$count}] " . $recipe->title,
                        "description" => $recipe->description,
                        "method_id" => $recipe->method_id,
                        "status" => RecipeStatus::SUBMITTED->value,
                        "created_by" => Auth::id(),
                        "updated_by" => Auth::id(),
                        "original_recipe_id" => $request->id
                    ]
                ]
            );
            $createRecipe = $this->crudController->create($recipeRequest, "Recipe");

            if (json_decode($createRecipe->getContent())->status) {
                $newRecipe = json_decode($createRecipe->getContent())->data;

                // Update image
                $recipeImage = parse_url($recipe->image_url);
                $newPath = env("APP_ENV") === "local" ? "/development/recipes/{$newRecipe->id}" : "/requests/{$newRecipe->id}";
                $copyImage = $this->copyFile("s3", $recipeImage["path"], $newPath . "/" . basename($recipeImage["path"]));

                if ($copyImage) {
                    Recipe::find($newRecipe->id)->update([
                        "image_url" => Storage::disk("s3")->url($newPath . "/" . basename($recipeImage["path"]))
                    ]);
                }

                // Create steps
                $steps = json_decode($recipe->steps);
                foreach ($steps as $step) {
                    $stepRequest = new CrudRequest();
                    $stepRequest->replace(
                        [
                            "payload" => [
                                "recipe_id" => $newRecipe->id,
                                "order" => $step->order,
                                "step" => $step->step,
                                "timer_seconds" => property_exists($step, "timer_seconds") ? $step->timer_seconds : null,
                                "video_starts_at" => property_exists($step, "video_starts_at") ? $step->video_starts_at : null,
                                "video_stops_at" => property_exists($step, "video_stops_at") ? $step->video_stops_at : null,
                                "created_by" => Auth::id(),
                                "updated_by" => Auth::id()
                            ]
                        ]
                    );
                    $this->crudController->create($stepRequest, "RecipeStep");
                }

                // Create ingredients
                $ingredients = json_decode($recipe->ingredients);
                foreach ($ingredients as $ingredient) {
                    $ingredientRequest = new CrudRequest();
                    $ingredientRequest->replace(
                        [
                            "payload" => [
                                "recipe_id" => $newRecipe->id,
                                "name" => $ingredient->name,
                                "quantity" => $ingredient->quantity,
                                "unit_id" => $ingredient->unit_id,
                                "created_by" => Auth::id(),
                                "updated_by" => Auth::id()
                            ]
                        ]
                    );
                    $this->crudController->create($ingredientRequest, "RecipeIngredient");
                }

                // Create meta
                $metadataRequest = new CrudRequest();
                $metadataRequest->replace([
                    "payload" => [
                        "recipe_id" => $newRecipe->id
                    ]
                ]);
                $this->crudController->create($metadataRequest, "RecipeMetadata");
            }

            DB::commit();

            $result = $this->dataController->index("Recipe", $newRecipe->id, "steps&ingredients");
            return $this->jsonResponse(data: json_decode($result->getContent())->data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function getRandom()
    {
        try {
            $recipe = Recipe::with('method')
                ->where("status", "APPROVED")
                ->where("configs->is_private", false)
                ->inRandomOrder()
                ->first();

            return $this->jsonResponse(data: $recipe);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function myRecipeList(MyRecipeRequest $request)
    {
        try {
            $query = Recipe::with(['method', 'user'])
                ->whereHas('userRecipes', function ($q) {
                    $q->where('user_id', Auth::id());
                })
                ->when(
                    $request->has('global_filter') && $request->has('global_filter_columns'),
                    function ($q) use ($request) {
                        $columns = array_map('trim', explode(',', $request->global_filter_columns));

                        $q->where(function ($sub) use ($columns, $request) {
                            foreach ($columns as $column) {
                                $sub->orWhere($column, 'LIKE', "%{$request->global_filter}%");
                            }
                        });
                    }
                )
                ->paginate($request->limit ?? 20)
                ->withQueryString();

            $result = [
                'total'     => $query->total(),
                'prev_page'  => $query->previousPageUrl(),
                'next_page'  => $query->nextPageUrl(),
                'rows'      => $query->items(),
                'page_count' => (int) ceil($query->total() / $query->perPage()),
                'page'      => $query->currentPage()
            ];

            return $this->jsonResponse(data: $result);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function addToRecipeBook(RecipeBookRequest $request)
    {
        try {
            UserRecipe::insert([
                "user_id" => Auth::id(),
                "recipe_id" => $request->id,
                "created_at" => now(),
                "updated_at" => now()
            ]);

            return $this->jsonResponse();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
    public function removeFromRecipeBook(RecipeBookRequest $request)
    {
        try {
            UserRecipe::where("user_id", Auth::id())->where("recipe_id", $request->id)->first()->delete();

            return $this->jsonResponse();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
}
