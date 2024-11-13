<?php

namespace App\Rules;

use App\Models\RecipeIngredient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;
use Validator;

class RecipeIngredientCrud implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (str_contains(request()->url(), "create")) {
            $this->checkPayload($value, $fail);
            $createValidator = Validator::make($value, [
                "recipe_id" => "required|string",
                "name" => "required|string",
                "unit_id" => "required|integer",
                "image_url" => "nullable|string"
            ]);
            if ($createValidator->fails()) {
                throw ValidationException::withMessages($createValidator->errors()->all());
            }
        }

        if (str_contains(request()->url(), "update")) {
            $this->checkPayload($value, $fail);
            $updateValidator = Validator::make($value, [
                "id" => "required|exists:recipe_ingredients,id",
                "recipe_id" => "nullable|string",
                "name" => "nullable|string",
                "unit_id" => "nullable|integer",
                "image_url" => "nullable|string"
            ]);
            if ($updateValidator->fails()) {
                throw ValidationException::withMessages($updateValidator->errors()->all());
            }
        }

        if (str_contains(request()->url(), "delete") || str_contains(request()->url(), "restore")) {
            $deleteValidator = Validator::make(
                $value,
                is_array($value["id"]) ? [
                    "id" => "required|array",
                    "id.*" => "exists:recipe_ingredients,id"
                ] : [
                    "id" => "required|exists:recipe_ingredients,id"
                ]
            );

            if ($deleteValidator->fails()) {
                throw ValidationException::withMessages($deleteValidator->errors()->all());
            }
        }
    }

    private function checkPayload(mixed $value, Closure $fail)
    {
        $model = new RecipeIngredient();
        $fillable = $model->getFillable();

        foreach ($value as $k => $v) {
            if (!in_array($k, $fillable)) {
                $fail(__("validation.column_not_exist", ["column" => $k, "class" => "RecipeIngredient"]));
            }
        }
    }
}
