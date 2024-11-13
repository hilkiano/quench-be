<?php

namespace App\Http\Requests\Data;

use App\Rules\ValidateColumn;
use App\Rules\ValidateModel;
use Illuminate\Foundation\Http\FormRequest;

class ComboboxRequest extends FormRequest
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
            "model" => ["required", "string", new ValidateModel],
            "label" => ["required_with:model", "string", new ValidateColumn],
            "value" => ["required_with:model", "string", new ValidateColumn]
        ];
    }
}
