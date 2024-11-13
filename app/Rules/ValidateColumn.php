<?php

namespace App\Rules;

use App\Traits\GeneralHelpers;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateColumn implements ValidationRule
{
    use GeneralHelpers;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $model = null;
        if (request()->has("model")) {
            $model = $this->checkModel(request()->model);
        }

        if ($model) {
            if (!$this->checkColumn($model, $value)) {
                $fail(__("validation.column_not_exist", ["column" => $attribute, "class" => request()->model]));
            }
        } else {
            $fail(__("validation.exists", ["attribute" => $attribute]));
        }
    }
}
