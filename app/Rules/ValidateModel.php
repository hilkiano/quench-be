<?php

namespace App\Rules;

use App\Traits\GeneralHelpers;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateModel implements ValidationRule
{
    use GeneralHelpers;
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->checkModel($value) === null) {
            $fail(__("validation.exists", ["attribute" => $attribute]));
        }
    }
}
