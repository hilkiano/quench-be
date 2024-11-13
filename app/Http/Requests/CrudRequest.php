<?php

namespace App\Http\Requests;

use App\Rules\RecipeCrud;
use App\Rules\RecipeIngredientCrud;
use App\Rules\RecipeStepCrud;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidateModel;

class CrudRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "model" => [
                "nullable",
                "string",
                new ValidateModel
            ],
            "payload" => [
                "required",
                "array",
                $this->additionalRules()
            ]
        ];
    }

    public function additionalRules($class = null)
    {
        if (!$class) {
            $class = request()->route()->parameter("model");
        }

        switch ($class) {
            case 'Recipe':
                return new RecipeCrud();
            case 'RecipeStep':
                return new RecipeStepCrud();
            case 'RecipeIngredient':
                return new RecipeIngredientCrud();
        }
    }
}
