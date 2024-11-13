<?php

namespace App\Http\Requests\Data;

use App\Rules\ValidateModel;
use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
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
            "model" => ['required', 'string', new ValidateModel],
            "limit" => "nullable|integer",
            "sort"  => "required_with:sort_direction|string",
            "sort_direction"  => "required_with:sort|in:asc,desc",
            "global_filter" => "required_with:global_filter_columns|string",
            "global_filter_columns" => "required_with:global_filter|string",
            "with_trashed" => "nullable|in:true,false",
            "relations" => "nullable|string",
            "relation_count" => "nullable|string",
            "filter" => "nullable|json"
        ];
    }
}
