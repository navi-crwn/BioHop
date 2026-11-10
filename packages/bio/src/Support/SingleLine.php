<?php

namespace Hop\Bio\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/*
| A value that ends up on one line of a file (the vCard of a contact block). A line break or a control character would
| let a visitor-facing value start a new line of that file, so the editor refuses it.
*/
class SingleLine implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/[\x00-\x08\x0A-\x1F\x7F\x{0085}\x{2028}\x{2029}]/u', $value)) {
            $fail(__('This field cannot contain line breaks.'));
        }
    }
}
