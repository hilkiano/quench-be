<?php

namespace App\Http\Requests\Recipe;

use App\Enums\RecipeStatus;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->hasHeader("X-Backoffice-Session")) {
            return true;
        }

        $user = Auth::user();
        return $user->is_administrator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "id" => "required|exists:recipes,id",
            "status" => ["required", Rule::enum(RecipeStatus::class)],
            'reason' => [
                Rule::requiredIf($this->input('status') === RecipeStatus::REJECTED->value),
            ],
            "approved_by" => "required|string"
        ];
    }
}
