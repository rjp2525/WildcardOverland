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

    /** Which form this stamp had to have been handed out with. */
    public function __construct(protected string $purpose) {}

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = $this->validator->getData();

        $passes = Honeypot::passes(
            $data[Honeypot::FIELD] ?? null,
            $data[Honeypot::STAMP] ?? null,
            $this->purpose,
        );

        if (! $passes) {
            $fail('That did not go through. Try again - the form has reloaded itself.');
        }
    }
}
