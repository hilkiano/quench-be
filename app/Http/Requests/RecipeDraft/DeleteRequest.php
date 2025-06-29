<?php

namespace App\Http\Requests\RecipeDraft;

use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Log;

class DeleteRequest extends FormRequest
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
        Log::info(print_r($this->request->all(), true));
        return [
            'id' => 'required|exists:recipe_drafts,id',
        ];
    }
}
