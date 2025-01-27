<?php

namespace App\Rules;

use App\Enums\RecipeStatus;
use App\Models\Recipe;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Validator;

class RecipeCrud implements ValidationRule
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
                "title" => "required|string|max:255",
                "description" => "nullable|string",
                "approved_at" => "nullable|date",
                "youtube_url" => "nullable|string",
                "configs" => "nullable|array"
            ]);
            if ($createValidator->fails()) {
                throw ValidationException::withMessages($createValidator->errors()->all());
            }
        }

        if (str_contains(request()->url(), "update")) {
            $this->checkPayload($value, $fail);
            $updateValidator = Validator::make($value, [
                "id" => "required|exists:recipes,id",
                "title" => "nullable|string|max:255",
                "status" => [
                    "nullable",
                    Rule::enum(RecipeStatus::class)
                ],
                "reason" => "required_if:status,REJECTED|string",
                "description" => "nullable|string",
                "approved_at" => "nullable|date",
                "youtube_url" => "nullable|string",
                "configs" => "nullable|array"
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
                    "id.*" => "exists:recipes,id"
                ] : [
                    "id" => "required|exists:recipes,id"
                ]
            );

            if ($deleteValidator->fails()) {
                throw ValidationException::withMessages($deleteValidator->errors()->all());
            }
        }
    }

    private function checkPayload(mixed $value, Closure $fail)
    {
        $model = new Recipe();
        $availableColumns = $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());
        $columns = [];
        foreach ($availableColumns as $column) {
            array_push($columns, $column["name"]);
        }

        foreach ($value as $k => $v) {
            if (!in_array($k, $columns)) {
                $fail(__("validation.column_not_exist", ["column" => $k, "class" => "Recipe"]));
            }
        }
    }
}
