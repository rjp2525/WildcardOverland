<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Thrown when an SVG cannot be parsed, and so cannot be made safe.
 *
 * It surfaces as a validation error on the file field rather than a 500,
 * because from where the person is standing that is what it is: the file
 * they picked is not usable.
 */
class UnreadableSvg extends RuntimeException
{
    public function __construct(protected string $filename)
    {
        parent::__construct("Could not read [{$filename}] as an SVG.");
    }

    public function render(): never
    {
        throw ValidationException::withMessages([
            'file' => "{$this->filename} could not be read as an SVG. Try exporting it again.",
        ]);
    }
}
