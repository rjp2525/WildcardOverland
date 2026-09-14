<?php

namespace App\Support\RichText;

use Illuminate\Support\Str;

/**
 * Renders a TipTap document.
 *
 * Rich text is stored as the editor's own JSON rather than as HTML, because
 * HTML in a database is a string someone has to trust. JSON is a tree we can
 * walk, and this is the only thing that turns it into markup. Anything not
 * on the allowlist below is dropped rather than passed through, so the
 * output cannot contain a tag or an attribute this class did not write.
 *
 * That is what makes it safe to hand the result to v-html: the browser is
 * rendering our markup, not the database's.
 */
class TipTap
{
    /** Nodes we know how to draw. Everything else is skipped. */
    protected const NODES = [
        'paragraph', 'heading', 'bulletList', 'orderedList', 'listItem',
        'blockquote', 'codeBlock', 'horizontalRule', 'hardBreak', 'text',
    ];

    /** Marks we know how to draw, innermost last. */
    protected const MARKS = ['link', 'bold', 'italic', 'strike', 'code'];

    /** Only these can start a URL. No javascript:, no data:. */
    protected const SCHEMES = ['http://', 'https://', 'mailto:', 'tel:', '/', '#'];

    /**
     * A document nested deeper than this is either a mistake or an attempt
     * to blow the stack, and neither is worth rendering.
     */
    protected const MAX_DEPTH = 24;

    public static function html(mixed $document): string
    {
        $nodes = static::content(static::document($document));

        return implode('', array_map(fn ($node) => static::node($node, 0), $nodes));
    }

    /**
     * The words on their own, for meta descriptions, cards and anywhere
     * else that has no room for markup.
     */
    public static function text(mixed $document, string $separator = ' '): string
    {
        $parts = [];

        static::walk(static::document($document), function (array $node) use (&$parts): void {
            if (($node['type'] ?? null) === 'text' && is_string($node['text'] ?? null)) {
                $parts[] = $node['text'];
            }

            if (($node['type'] ?? null) === 'hardBreak') {
                $parts[] = ' ';
            }
        });

        return trim(preg_replace('/\s+/u', ' ', implode($separator, $parts)) ?? '');
    }

    /**
     * Whether there is anything in it.
     *
     * The editor leaves a single empty paragraph behind when you delete
     * everything, which is not the same as never having written anything but
     * should look the same on the page.
     */
    public static function isEmpty(mixed $document): bool
    {
        return static::text($document) === '' && ! static::hasDrawnNode($document);
    }

    /**
     * Normalise whatever came in.
     *
     * Accepts the decoded array, a JSON string, and plain text, which is
     * what a field that was never rich contains.
     *
     * @return array<string, mixed>
     */
    public static function document(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            $value = is_array($decoded) ? $decoded : static::fromText($value);
        }

        if (! is_array($value)) {
            return ['type' => 'doc', 'content' => []];
        }

        // A bare array of nodes is a valid thing to be handed.
        if (! isset($value['type'])) {
            return ['type' => 'doc', 'content' => array_values($value)];
        }

        return $value['type'] === 'doc' ? $value : ['type' => 'doc', 'content' => [$value]];
    }

    /**
     * Plain text as a document, one paragraph per blank line.
     *
     * @return array<string, mixed>
     */
    public static function fromText(string $text): array
    {
        $paragraphs = preg_split('/\R{2,}/u', trim($text)) ?: [];

        $content = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            $content[] = [
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $paragraph]],
            ];
        }

        return ['type' => 'doc', 'content' => $content];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected static function node(array $node, int $depth): string
    {
        if ($depth > static::MAX_DEPTH) {
            return '';
        }

        $type = $node['type'] ?? null;

        if (! is_string($type) || ! in_array($type, static::NODES, true)) {
            return '';
        }

        if ($type === 'text') {
            return static::marks($node);
        }

        if ($type === 'hardBreak') {
            return '<br>';
        }

        if ($type === 'horizontalRule') {
            return '<hr>';
        }

        $inner = implode('', array_map(
            fn ($child) => static::node($child, $depth + 1),
            static::content($node),
        ));

        return match ($type) {
            'paragraph' => $inner === '' ? '' : "<p>{$inner}</p>",
            'heading' => static::heading($node, $inner),
            'bulletList' => "<ul>{$inner}</ul>",
            'orderedList' => static::orderedList($node, $inner),
            'listItem' => "<li>{$inner}</li>",
            'blockquote' => "<blockquote>{$inner}</blockquote>",
            'codeBlock' => '<pre><code>'.static::escape(static::text($node)).'</code></pre>',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected static function heading(array $node, string $inner): string
    {
        // The editor offers two levels. The page already owns its h1.
        $level = (int) ($node['attrs']['level'] ?? 2);
        $level = max(2, min(4, $level));

        return "<h{$level}>{$inner}</h{$level}>";
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected static function orderedList(array $node, string $inner): string
    {
        $start = (int) ($node['attrs']['start'] ?? 1);

        return $start > 1
            ? '<ol start="'.$start.'">'.$inner.'</ol>'
            : "<ol>{$inner}</ol>";
    }

    /**
     * A run of text with its marks wrapped around it.
     *
     * @param  array<string, mixed>  $node
     */
    protected static function marks(array $node): string
    {
        $text = $node['text'] ?? null;

        if (! is_string($text) || $text === '') {
            return '';
        }

        $html = static::escape($text);
        $marks = is_array($node['marks'] ?? null) ? $node['marks'] : [];

        // Reversed so the order in the array ends up as the nesting order.
        foreach (array_reverse($marks) as $mark) {
            $html = static::mark(is_array($mark) ? $mark : [], $html);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $mark
     */
    protected static function mark(array $mark, string $html): string
    {
        $type = $mark['type'] ?? null;

        if (! is_string($type) || ! in_array($type, static::MARKS, true)) {
            return $html;
        }

        return match ($type) {
            'bold' => "<strong>{$html}</strong>",
            'italic' => "<em>{$html}</em>",
            'strike' => "<s>{$html}</s>",
            'code' => "<code>{$html}</code>",
            'link' => static::link($mark, $html),
            default => $html,
        };
    }

    /**
     * @param  array<string, mixed>  $mark
     */
    protected static function link(array $mark, string $html): string
    {
        $href = $mark['attrs']['href'] ?? null;

        if (! is_string($href) || ! static::allowedHref($href)) {
            // A link we will not follow is still text worth keeping.
            return $html;
        }

        $external = Str::startsWith($href, ['http://', 'https://'])
            && ! Str::startsWith($href, rtrim((string) config('app.url'), '/'));

        $attributes = 'href="'.static::escape($href).'"';

        if ($external) {
            $attributes .= ' target="_blank" rel="noopener noreferrer"';
        }

        return "<a {$attributes}>{$html}</a>";
    }

    protected static function allowedHref(string $href): bool
    {
        $href = trim($href);

        if ($href === '') {
            return false;
        }

        /*
         * Compared against the start of the string rather than parsed,
         * because "java\tscript:" and friends parse as relative URLs in
         * some parsers and as script in some browsers.
         */
        return Str::startsWith(strtolower($href), static::SCHEMES);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<int, array<string, mixed>>
     */
    protected static function content(array $node): array
    {
        $content = $node['content'] ?? [];

        return is_array($content)
            ? array_values(array_filter($content, is_array(...)))
            : [];
    }

    /**
     * Depth-first over every node, including the root.
     *
     * @param  array<string, mixed>  $node
     */
    protected static function walk(array $node, callable $visit, int $depth = 0): void
    {
        if ($depth > static::MAX_DEPTH) {
            return;
        }

        $visit($node);

        foreach (static::content($node) as $child) {
            static::walk($child, $visit, $depth + 1);
        }
    }

    /** A rule or an image carries no words but is still something. */
    protected static function hasDrawnNode(mixed $document): bool
    {
        $found = false;

        static::walk(static::document($document), function (array $node) use (&$found): void {
            if (in_array($node['type'] ?? null, ['horizontalRule'], true)) {
                $found = true;
            }
        });

        return $found;
    }

    protected static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
