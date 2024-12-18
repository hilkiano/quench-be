<?php

namespace App\Http\Controllers;

use App\Enums\RecipeStatus;
use App\Http\Requests\CrudRequest;
use App\Http\Requests\Recipe\CreateRequest;
use App\Http\Requests\Recipe\UpdateRequest;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Traits\GeneralHelpers;
use Auth;
use DB;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    use GeneralHelpers;

    private $crudController;
    private $dataController;

    public function __construct()
    {
        $this->crudController = new CrudController();
        $this->dataController = new DataController();
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
                        "status" => RecipeStatus::SUBMITTED->value,
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
                "status" => RecipeStatus::SUBMITTED->value,
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
}
