<?php

namespace App\Http\Requests\RecipeDraft;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
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
            'id' => 'nullable',
            'basic_info' => 'nullable|json',
            'steps' => 'nullable|json',
            'ingredients' => 'nullable|json',
            'image' => 'nullable|image'
        ];
    }
}
