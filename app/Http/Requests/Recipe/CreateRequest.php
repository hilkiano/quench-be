<?php

namespace App\Http\Requests\Recipe;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "title" => "required|string|max:255",
            "description" => "nullable|string",
            "ingredients" => "required|array",
            "ingredients.*.name" => "required|string",
            "ingredients.*.quantity" => "required|numeric",
            "ingredients.*.unit" => "required|numeric",
            "steps" => "required|array",
            "steps.*.order" => "required|numeric",
            "steps.*.step" => "required|string"
        ];
    }
}
