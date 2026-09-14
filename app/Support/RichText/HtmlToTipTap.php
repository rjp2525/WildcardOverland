<?php

namespace App\Support\RichText;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Turns the HTML the editor used to save into the JSON it saves now.
 *
 * Only needed for content written before the change. It understands exactly
 * the tags TipTap renders and nothing else, so anything unexpected in an old
 * row contributes its text and loses its markup, which is the right way
 * round: no content disappears, no unknown markup survives.
 */
class HtmlToTipTap
{
    protected const BLOCKS = [
        'p' => 'paragraph',
        'h1' => 'heading',
        'h2' => 'heading',
        'h3' => 'heading',
        'h4' => 'heading',
        'ul' => 'bulletList',
        'ol' => 'orderedList',
        'li' => 'listItem',
        'blockquote' => 'blockquote',
        'pre' => 'codeBlock',
        'hr' => 'horizontalRule',
    ];

    protected const MARKS = [
        'strong' => 'bold',
        'b' => 'bold',
        'em' => 'italic',
        'i' => 'italic',
        's' => 'strike',
        'del' => 'strike',
        'strike' => 'strike',
        'code' => 'code',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function convert(?string $html): array
    {
        $html = trim((string) $html);

        if ($html === '') {
            return ['type' => 'doc', 'content' => []];
        }

        $body = static::parse($html);

        if ($body === null) {
            return TipTap::fromText(strip_tags($html));
        }

        $content = static::children($body, []);

        /*
         * Loose text that was never inside a block still needs a home, or
         * an old row saved as a bare sentence would come back empty.
         */
        $content = static::wrapLooseInline($content);

        return ['type' => 'doc', 'content' => array_values($content)];
    }

    protected static function parse(string $html): ?DOMNode
    {
        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument;
        // No LIBXML_NOENT: substituting entities is how XXE gets in, and
        // nothing in an editor's output needs it.
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="tiptap-root">'.$html.'</div>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $document->getElementById('tiptap-root') : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $marks
     * @return array<int, array<string, mixed>>
     */
    protected static function children(DOMNode $node, array $marks): array
    {
        $out = [];

        foreach ($node->childNodes as $child) {
            $out = [...$out, ...static::convertNode($child, $marks)];
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $marks
     * @return array<int, array<string, mixed>>
     */
    protected static function convertNode(DOMNode $node, array $marks): array
    {
        if ($node instanceof DOMText) {
            $text = $node->textContent;

            return trim($text) === ''
                ? []
                : [array_filter([
                    'type' => 'text',
                    'text' => preg_replace('/\s+/u', ' ', $text),
                    'marks' => $marks,
                ], fn ($value) => $value !== [])];
        }

        if (! $node instanceof DOMElement) {
            return [];
        }

        $tag = strtolower($node->nodeName);

        if ($tag === 'br') {
            return [['type' => 'hardBreak']];
        }

        if ($tag === 'hr') {
            return [['type' => 'horizontalRule']];
        }

        if ($tag === 'a') {
            $href = $node->getAttribute('href');

            return static::children($node, $href === ''
                ? $marks
                : [...$marks, ['type' => 'link', 'attrs' => ['href' => $href]]]);
        }

        if (isset(static::MARKS[$tag])) {
            return static::children($node, [...$marks, ['type' => static::MARKS[$tag]]]);
        }

        if (isset(static::BLOCKS[$tag])) {
            return [static::block($node, $tag, $marks)];
        }

        // A div, a span, anything else: keep what is inside, lose the box.
        return static::children($node, $marks);
    }

    /**
     * @param  array<int, array<string, mixed>>  $marks
     * @return array<string, mixed>
     */
    protected static function block(DOMElement $node, string $tag, array $marks): array
    {
        $type = static::BLOCKS[$tag];

        if ($type === 'horizontalRule') {
            return ['type' => 'horizontalRule'];
        }

        if ($type === 'codeBlock') {
            return [
                'type' => 'codeBlock',
                'content' => [['type' => 'text', 'text' => $node->textContent]],
            ];
        }

        $block = ['type' => $type];

        if ($type === 'heading') {
            // The editor only offers 2 and 3, and the page owns its own h1.
            $level = (int) substr($tag, 1);
            $block['attrs'] = ['level' => max(2, min(3, $level))];
        }

        $content = static::children($node, $marks);

        // A list item holding bare text needs the paragraph TipTap expects.
        if ($type === 'listItem') {
            $content = static::wrapLooseInline($content);
        }

        if ($content !== []) {
            $block['content'] = array_values($content);
        }

        return $block;
    }

    /**
     * Gathers runs of inline nodes into paragraphs, leaving blocks alone.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    protected static function wrapLooseInline(array $nodes): array
    {
        $out = [];
        $buffer = [];

        $flush = function () use (&$out, &$buffer): void {
            if ($buffer !== []) {
                $out[] = ['type' => 'paragraph', 'content' => array_values($buffer)];
                $buffer = [];
            }
        };

        foreach ($nodes as $node) {
            if (in_array($node['type'] ?? null, ['text', 'hardBreak'], true)) {
                $buffer[] = $node;

                continue;
            }

            $flush();
            $out[] = $node;
        }

        $flush();

        return $out;
    }
}
