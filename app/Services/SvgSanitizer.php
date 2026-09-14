<?php

namespace App\Services;

use enshrined\svgSanitize\Sanitizer;

/**
 * Cleans an SVG before it is ever written to disk.
 *
 * An SVG is a document, not a picture. Inside an <img> tag a browser will
 * not run its scripts, but these are served from our own origin under a
 * signed URL, and anyone who opens that URL directly gets it as a page. A
 * <script> or an onload attribute in there would be running on our domain.
 *
 * So the bytes are stripped on the way in rather than on the way out, and
 * what lands on the disk is already safe. AssetController adds a locked
 * down CSP on top, because two doors are better than one.
 */
class SvgSanitizer
{
    public function __construct(protected Sanitizer $sanitizer)
    {
        // Nothing an image needs is out here, and both are how an SVG
        // reaches back out to something else.
        $this->sanitizer->removeRemoteReferences(true);
        $this->sanitizer->minify(true);
    }

    /**
     * @return string|null The cleaned markup, or null if it is not SVG at all.
     */
    public function clean(string $markup): ?string
    {
        $clean = $this->sanitizer->sanitize($markup);

        // The library returns false on markup it cannot parse. An SVG that
        // will not parse is not one we want to store.
        return is_string($clean) && $clean !== '' ? $clean : null;
    }

    /**
     * The drawing's own size, for the width and height attributes.
     *
     * getimagesize cannot read SVG, so this reads the width and height, and
     * falls back to the viewBox, which is what most exported logos carry
     * instead of pixel dimensions.
     *
     * @return array{0: int|null, 1: int|null}
     */
    public function dimensions(string $markup): array
    {
        $attributes = $this->rootAttributes($markup);

        $width = $this->length($attributes['width'] ?? null);
        $height = $this->length($attributes['height'] ?? null);

        if ($width !== null && $height !== null) {
            return [$width, $height];
        }

        $box = preg_split('/[\s,]+/', trim((string) ($attributes['viewBox'] ?? '')));

        if (is_array($box) && count($box) === 4) {
            return [
                $width ?? (int) round((float) $box[2]),
                $height ?? (int) round((float) $box[3]),
            ];
        }

        return [$width, $height];
    }

    /**
     * @return array<string, string>
     */
    protected function rootAttributes(string $markup): array
    {
        $previous = libxml_use_internal_errors(true);

        /*
         * No LIBXML_NOENT. Substituting entities is exactly the XXE hole,
         * and nothing here needs them: this only reads width, height and
         * viewBox off the root element.
         */
        $document = new \DOMDocument;
        $parsed = $document->loadXML($markup, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $parsed || ! $document->documentElement) {
            return [];
        }

        $attributes = [];

        foreach ($document->documentElement->attributes as $attribute) {
            $attributes[$attribute->nodeName] = $attribute->nodeValue ?? '';
        }

        return $attributes;
    }

    /**
     * SVG lengths can carry a unit. Only absolute pixel values are useful
     * here; a percentage says nothing about how big the drawing is.
     */
    protected function length(?string $value): ?int
    {
        if ($value === null || ! preg_match('/^\s*([\d.]+)\s*(px)?\s*$/i', $value, $match)) {
            return null;
        }

        $number = (float) $match[1];

        return $number > 0 ? (int) round($number) : null;
    }
}
