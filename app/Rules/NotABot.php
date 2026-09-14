<?php

namespace App\Rules;

use App\Support\Honeypot;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

/**
 * The honeypot, as a rule so it fails the form rather than the request.
 *
 * The message is deliberately vague and lands on the submission as a whole.
 * Telling a script which of the two checks it failed is telling it how to
 * pass next time, and a person will never see this message because a person
 * cannot fail it.
 */
class NotABot implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = $this->validator->getData();

        if (! Honeypot::passes($data[Honeypot::FIELD] ?? null, $data[Honeypot::STAMP] ?? null)) {
            $fail('That did not go through. Reload the page and try again.');
        }
    }
}
