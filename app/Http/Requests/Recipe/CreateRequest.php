<?php

namespace App\Http\Requests\Recipe;

use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

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
            "method_id" => "required|exists:methods,id",
            "description" => "nullable|string",
            "ingredients" => "required|json",
            "steps" => "required|json",
            "image" => "required|image"
        ];
    }
}
