<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts a TipTap document and nothing else.
 *
 * The renderer already drops anything it does not recognise, so this is not
 * what makes the output safe. It is here so a malformed payload fails at the
 * form with a message rather than being stored and quietly rendering as
 * nothing later.
 */
class RichTextDocument implements ValidationRule
{
    public function __construct(
        /** Roughly how many characters of actual words are allowed. */
        protected int $maxLength = 50000,
        protected int $maxDepth = 24,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_array($value)) {
            $fail('The :attribute is not formatted text.');

            return;
        }

        if (($value['type'] ?? null) !== 'doc') {
            $fail('The :attribute is not formatted text.');

            return;
        }

        if ($this->depth($value) > $this->maxDepth) {
            $fail('The :attribute is nested too deeply.');

            return;
        }

        if (strlen(json_encode($value) ?: '') > $this->maxLength) {
            $fail('The :attribute is too long.');
        }
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function depth(array $node, int $depth = 0): int
    {
        if ($depth > $this->maxDepth) {
            return $depth;
        }

        $content = $node['content'] ?? null;

        if (! is_array($content)) {
            return $depth;
        }

        $deepest = $depth;

        foreach ($content as $child) {
            if (is_array($child)) {
                $deepest = max($deepest, $this->depth($child, $depth + 1));
            }
        }

        return $deepest;
    }
}
