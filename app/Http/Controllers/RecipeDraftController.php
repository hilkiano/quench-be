<?php

namespace App\Http\Controllers;

use App\Enums\RecipeStatus;
use App\Http\Requests\CrudRequest;
use App\Http\Requests\RecipeDraft\DeleteRequest;
use App\Http\Requests\RecipeDraft\SaveRequest;
use App\Models\Recipe;
use App\Models\RecipeDraft;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use Illuminate\Support\Facades\Log;
use App\Traits\GeneralHelpers;
use Auth;
use DB;
use Illuminate\Support\Facades\Storage;
use Str;

class RecipeDraftController extends Controller
{
    use GeneralHelpers;

    private $crudController;

    public function __construct()
    {
        $this->crudController = new CrudController();
    }

    public function save(SaveRequest $request)
    {
        try {
            // Format the data
            $value = $this->getFormData($request);

            // Update or create new draft
            if ($request->id) {
                $draft = RecipeDraft::find((int) $request->id);
                $draft->data = $value;
                $draft->created_by = Auth::id();
                $draft->updated_by = Auth::id();

                if ($request->image) {
                    // Delete old image
                    if ($draft->image_url) {
                        $parsedUrl = parse_url($draft->image_url);
                        $this->deleteFile("s3", $parsedUrl["path"]);
                    }

                    $draft->image_url = $this->storeImage("s3", $request->file("image"), "draft/$request->id");
                }

                $draft->save();
            } else {
                // Delete old draft data if the user's draft count reaches the allowed length.
                $drafts = RecipeDraft::where("created_by", Auth::id())->orderBy("created_at", "desc")->get();

                if (count($drafts) === (int) env("DRAFT_MAX")) {
                    $oldestDraft = $drafts->last();
                    RecipeDraft::where("id", $oldestDraft->id)->delete();
                }

                $draft = new RecipeDraft();
                $draft->data = $value;
                $draft->created_by = Auth::id();
                $draft->updated_by = Auth::id();

                $draft->save();

                if ($request->image) {
                    $draft = RecipeDraft::find($draft->id);
                    $draft->image_url = $this->storeImage("s3", $request->file("image"), "draft/$draft->id");

                    $draft->save();
                }
            }

            return $this->jsonResponse(data: $draft->id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function delete(int $id)
    {
        try {
            $draft = RecipeDraft::find($id);

            if ($draft->image_url) {
                $path = env("APP_ENV") === "local" ? "development/draft/{$draft->id}" : "draft/{$draft->id}";
                $this->deleteDirectory("s3", $path);
            }

            $draft->delete();

            return $this->jsonResponse(data: $draft);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    private function getFormData(SaveRequest $request)
    {
        $arrayData = [
            "basic_info" => $request->basic_info ? json_decode($request->basic_info) : null,
            "ingredients" => $request->ingredients ? json_decode($request->ingredients) : null,
            "steps" => $request->steps ? json_decode($request->steps) : null,
        ];

        if ($request->id) {
            $oldData = RecipeDraft::find($request->id);

            $arrayData["basic_info"] = $oldData->data["basic_info"] ? array_replace($oldData->data["basic_info"], (array) $arrayData["basic_info"]) : (array) $arrayData["basic_info"];
            $arrayData["ingredients"] = $request->ingredients ? (array) $arrayData["ingredients"] : $oldData->data["ingredients"];
            $arrayData["steps"] = $request->steps ? (array) $arrayData["steps"] : $oldData->data["steps"];
        }

        return $arrayData;
    }

    public function submitDraft(int $id)
    {
        try {
            $draft = RecipeDraft::find($id);

            if ($draft) {
                DB::beginTransaction();

                // If draft has recipe_id, update the recipe. Otherwise, create.
                if ($draft->recipe_id) {
                    $recipe = Recipe::find($draft->recipe_id);
                } else {
                    $recipe = new Recipe();
                }

                $recipe->title = $draft->data["basic_info"]["title"];
                $recipe->method_id = (int) $draft->data["basic_info"]["method_id"];
                $recipe->description = $draft->data["basic_info"]["description"];
                $recipe->status = RecipeStatus::SUBMITTED->value;
                $recipe->created_by = Auth::id();
                $recipe->updated_by = Auth::id();

                $recipe->save();

                // If draft has recipe_id, delete old image and copy from draft
                if ($draft->recipe_id) {
                    $parsedUrl = parse_url($recipe->image_url);
                    $this->deleteFile("s3", $parsedUrl["path"]);
                }

                // Copy image
                $draftImage = parse_url($draft->image_url);
                $newPath = env("APP_ENV") === "local" ? "/development/recipes/{$recipe->id}" : "/recipes/{$recipe->id}";
                $copyImage = $this->copyFile("s3", $draftImage["path"], $newPath . "/" . basename($draftImage["path"]));

                if ($copyImage) {
                    Recipe::find($recipe->id)->update([
                        "image_url" => Storage::disk("s3")->url($newPath . "/" . basename($draftImage["path"]))
                    ]);
                }

                // If draft has recipe_id, delete all ingredients. Otherwise, only create.
                if ($draft->recipe_id) {
                    RecipeIngredient::where("recipe_id", $draft->recipe_id)->delete();
                }

                foreach ($draft->data["ingredients"] as $ingredient) {
                    $newIngredient = new RecipeIngredient();

                    $newIngredient->recipe_id = $recipe->id;
                    $newIngredient->name = $ingredient["name"];
                    $newIngredient->quantity = $ingredient["quantity"];
                    $newIngredient->unit_id = $ingredient["unit_id"];
                    $newIngredient->created_by = Auth::id();
                    $newIngredient->updated_by = Auth::id();

                    $newIngredient->save();
                }

                // If draft has recipe_id, delete all steps. Otherwise, only create.
                if ($draft->recipe_id) {
                    RecipeStep::where("recipe_id", $draft->recipe_id)->delete();
                }

                foreach ($draft->data["steps"] as $step) {
                    $newStep = new RecipeStep();

                    $newStep->recipe_id = $recipe->id;
                    $newStep->step = $step["step"];
                    $newStep->order = $step["order"];
                    $newStep->timer_seconds = $step["timer_seconds"];
                    $newStep->created_by = Auth::id();
                    $newStep->updated_by = Auth::id();


                    $newStep->save();
                }

                if (!$draft->recipe_id) {
                    $metadataRequest = new CrudRequest();
                    $metadataRequest->replace([
                        "payload" => [
                            "recipe_id" => $recipe->id
                        ]
                    ]);
                    $this->crudController->create($metadataRequest, "RecipeMetadata");
                }

                // Delete draft
                if ($draft->image_url) {
                    $path = env("APP_ENV") === "local" ? "development/draft/{$draft->id}" : "draft/{$draft->id}";
                    $this->deleteDirectory("s3", $path);
                }

                $draft->delete();

                DB::commit();

                return $this->jsonResponse(data: $recipe);
            } else {
                throw new \Exception("Draft id {$id} cannot be found.");
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function makeDraft(string $id)
    {
        try {
            $existingDraft = RecipeDraft::where("recipe_id", $id)->first();
            if ($existingDraft) {
                return $this->jsonResponse(data: $existingDraft->id);
            }

            $recipe = Recipe::with(["steps", "ingredients"])->find($id);

            DB::beginTransaction();
            $newDraft = new RecipeDraft();

            $newDraft->data = $this->prepareDraftData($recipe);
            $newDraft->recipe_id = $id;
            $newDraft->created_by = Auth::id();
            $newDraft->updated_by = Auth::id();

            $newDraft->save();

            // Copy image
            RecipeDraft::where("id", $newDraft->id)->first()->update([
                "image_url" => $this->copyToDraftImage($recipe->image_url, $newDraft->id)
            ]);

            DB::commit();

            return $this->jsonResponse(data: $newDraft->id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    private function prepareDraftData(Recipe $recipe)
    {
        $dataContent = [
            "basic_info" => [
                "title" => $recipe->title,
                "method_id" => (string) $recipe->method_id,
                "description" => $recipe->description
            ],
            "steps" => [],
            "ingredients" => []
        ];

        foreach ($recipe->ingredients as $ingredient) {
            array_push($dataContent["ingredients"], [
                "uuid"  => Str::uuid(),
                "name"  => $ingredient->name,
                "unit_id" => $ingredient->unit_id,
                "quantity" => $ingredient->quantity
            ]);
        }

        foreach ($recipe->steps as $step) {
            array_push($dataContent["steps"], [
                "uuid"  => Str::uuid(),
                "step"  => $step->step,
                "order" => $step->order,
                "timer_seconds" => $step->timer_seconds
            ]);
        }

        return $dataContent;
    }

    private function copyToDraftImage(string $url, int $id)
    {
        $sourceUrl = parse_url($url);
        $baseName = basename($sourceUrl["path"]);
        $targetPath = env("APP_ENV") === "local" ? "development/draft/{$id}/{$baseName}" : "draft/{$id}/{$baseName}";

        Storage::disk("s3")->copy($sourceUrl["path"], $targetPath);

        return Storage::disk("s3")->url($targetPath);
    }
}
