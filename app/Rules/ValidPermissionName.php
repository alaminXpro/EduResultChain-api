<?php

namespace Vanguard\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPermissionName implements ValidationRule
{
    protected string $regex = '/^[a-zA-Z0-9\-_\.]+$/';

    public function passes(string $attribute, mixed $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match($this->regex, $value) > 0;
    }

    public function __toString()
    {
        return sprintf('regex:%s', $this->regex);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->passes($attribute, $value)) {
            $fail(__('validation.regex', ['attribute' => __('permission name')]));
        }
    }
}
