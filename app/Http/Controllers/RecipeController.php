<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrudRequest;
use App\Http\Requests\Recipe\CreateRequest;
use App\Traits\GeneralHelpers;
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
                        "description" => $request->has("description") ? $request->description : null
                    ]
                ]
            );
            $createRecipe = $this->crudController->create($recipeRequest, "Recipe");
            if (json_decode($createRecipe->getContent())->status) {
                // Create steps
                foreach ($request->steps as $step) {
                    $stepRequest = new CrudRequest();
                    $stepRequest->replace(
                        [
                            "payload" => [
                                "recipe_id" => json_decode($createRecipe->getContent())->data->id,
                                "order" => $step["order"],
                                "step" => $step["step"]
                            ]
                        ]
                    );
                    $this->crudController->create($stepRequest, "RecipeStep");
                }

                // Create ingredients
                foreach ($request->ingredients as $ingredient) {
                    $ingredientRequest = new CrudRequest();
                    $ingredientRequest->replace(
                        [
                            "payload" => [
                                "recipe_id" => json_decode($createRecipe->getContent())->data->id,
                                "name" => $ingredient["name"],
                                "quantity" => $ingredient["quantity"],
                                "unit_id" => $ingredient["unit"]
                            ]
                        ]
                    );
                    $this->crudController->create($ingredientRequest, "RecipeIngredient");
                }
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
}
