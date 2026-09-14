<?php

namespace Tests\Unit;

use App\Support\RichText\HtmlToTipTap;
use App\Support\RichText\TipTap;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The renderer is the only thing that turns stored content into markup, so
 * it is the only place that has to be right about what a browser will do
 * with it.
 */
class TipTapTest extends TestCase
{
    protected function doc(array ...$content): array
    {
        return ['type' => 'doc', 'content' => $content];
    }

    protected function text(string $text, array $marks = []): array
    {
        return array_filter(['type' => 'text', 'text' => $text, 'marks' => $marks], fn ($v) => $v !== []);
    }

    protected function para(array ...$content): array
    {
        return ['type' => 'paragraph', 'content' => $content];
    }

    public function test_it_renders_the_marks_the_editor_offers(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('Leave it '),
            $this->text('alone', [['type' => 'bold']]),
            $this->text(' for '),
            $this->text('60 seconds', [['type' => 'italic']]),
        )));

        $this->assertSame('<p>Leave it <strong>alone</strong> for <em>60 seconds</em></p>', $html);
    }

    public function test_it_renders_lists_and_headings(): void
    {
        $html = TipTap::html($this->doc(
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [$this->text('Prep')]],
            ['type' => 'orderedList', 'content' => [
                ['type' => 'listItem', 'content' => [$this->para($this->text('Cook the rice.'))]],
                ['type' => 'listItem', 'content' => [$this->para($this->text('Chill it.'))]],
            ]],
        ));

        $this->assertSame(
            '<h2>Prep</h2><ol><li><p>Cook the rice.</p></li><li><p>Chill it.</p></li></ol>',
            $html,
        );
    }

    public function test_text_is_escaped_not_passed_through(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('<script>alert(1)</script> & "quotes"'),
        )));

        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame('<p>&lt;script&gt;alert(1)&lt;/script&gt; &amp; &quot;quotes&quot;</p>', $html);
    }

    public function test_a_node_type_we_do_not_know_is_dropped(): void
    {
        $html = TipTap::html($this->doc(
            ['type' => 'image', 'attrs' => ['src' => 'x', 'onerror' => 'alert(1)']],
            ['type' => 'iframe', 'attrs' => ['src' => 'https://evil.test']],
            $this->para($this->text('Kept.')),
        ));

        $this->assertSame('<p>Kept.</p>', $html);
    }

    public function test_a_mark_we_do_not_know_is_dropped_but_its_text_is_kept(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('Still here', [['type' => 'highlight', 'attrs' => ['style' => 'x']]]),
        )));

        $this->assertSame('<p>Still here</p>', $html);
    }

    #[DataProvider('dangerousLinks')]
    public function test_a_link_that_is_not_a_link_loses_its_href(string $href): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('Click', [['type' => 'link', 'attrs' => ['href' => $href]]]),
        )));

        $this->assertStringNotContainsString('href', $html);
        $this->assertSame('<p>Click</p>', $html);
    }

    public static function dangerousLinks(): array
    {
        return [
            ['javascript:alert(1)'],
            ['JaVaScRiPt:alert(1)'],
            [' javascript:alert(1)'],
            ['data:text/html;base64,PHNjcmlwdD4='],
            ['vbscript:msgbox(1)'],
            ['file:///etc/passwd'],
        ];
    }

    public function test_an_ordinary_link_survives_and_goes_out_marked_external(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('Serious Eats', [['type' => 'link', 'attrs' => ['href' => 'https://seriouseats.com']]]),
        )));

        $this->assertStringContainsString('href="https://seriouseats.com"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function test_a_link_back_to_our_own_site_does_not_open_a_tab(): void
    {
        config(['app.url' => 'https://wildcardoverland.com']);

        $html = TipTap::html($this->doc($this->para(
            $this->text('a trip', [['type' => 'link', 'attrs' => ['href' => 'https://wildcardoverland.com/trips/baja']]]),
        )));

        $this->assertStringNotContainsString('target=', $html);
    }

    public function test_an_href_with_quotes_cannot_break_out_of_the_attribute(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('x', [['type' => 'link', 'attrs' => ['href' => 'https://a.test/" onmouseover="alert(1)']]]),
        )));

        $this->assertStringNotContainsString('onmouseover="alert', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    public function test_it_will_not_recurse_forever(): void
    {
        $node = $this->para($this->text('deep'));

        for ($i = 0; $i < 200; $i++) {
            $node = ['type' => 'blockquote', 'content' => [$node]];
        }

        // The point is that it returns at all rather than exhausting the stack.
        $this->assertIsString(TipTap::html($this->doc($node)));
    }

    public function test_plain_text_extraction_for_meta_descriptions(): void
    {
        $doc = $this->doc(
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [$this->text('Prep at home')]],
            $this->para($this->text('Cook the rice '), $this->text('the day before', [['type' => 'bold']])),
        );

        $this->assertSame('Prep at home Cook the rice the day before', TipTap::text($doc));
    }

    public function test_an_empty_document_is_recognised_however_it_arrives(): void
    {
        $this->assertTrue(TipTap::isEmpty(null));
        $this->assertTrue(TipTap::isEmpty(''));
        $this->assertTrue(TipTap::isEmpty(['type' => 'doc', 'content' => []]));
        // What the editor leaves behind when you delete everything.
        $this->assertTrue(TipTap::isEmpty($this->doc(['type' => 'paragraph'])));
        $this->assertFalse(TipTap::isEmpty($this->doc($this->para($this->text('x')))));
    }

    public function test_an_empty_paragraph_renders_as_nothing(): void
    {
        $this->assertSame('', TipTap::html($this->doc(['type' => 'paragraph'])));
    }

    public function test_plain_text_is_accepted_as_a_document(): void
    {
        $html = TipTap::html("First beat.\n\nSecond beat.");

        $this->assertSame('<p>First beat.</p><p>Second beat.</p>', $html);
    }

    public function test_json_arrives_as_a_string_from_the_database(): void
    {
        $json = json_encode($this->doc($this->para($this->text('Stored.'))));

        $this->assertSame('<p>Stored.</p>', TipTap::html($json));
    }

    public function test_old_html_converts_and_renders_the_same(): void
    {
        $html = '<p>Cook the rice <strong>the day before</strong>.</p>'
            .'<ul><li>Spread it on sheet pans</li><li>Refrigerate overnight</li></ul>'
            .'<p><a href="https://seriouseats.com">Serious Eats</a></p>';

        $doc = HtmlToTipTap::convert($html);

        $this->assertSame('doc', $doc['type']);

        $rendered = TipTap::html($doc);

        $this->assertStringContainsString('<strong>the day before</strong>', $rendered);
        $this->assertStringContainsString('<li><p>Spread it on sheet pans</p></li>', $rendered);
        $this->assertStringContainsString('href="https://seriouseats.com"', $rendered);
    }

    public function test_converting_old_html_drops_markup_it_does_not_know_but_keeps_the_words(): void
    {
        $doc = HtmlToTipTap::convert('<div><span style="x">Kept</span><script>alert(1)</script></div>');

        $rendered = TipTap::html($doc);

        $this->assertStringContainsString('Kept', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringNotContainsString('style', $rendered);
    }

    public function test_converting_a_bare_sentence_still_produces_a_paragraph(): void
    {
        $this->assertSame('<p>No tags at all.</p>', TipTap::html(HtmlToTipTap::convert('No tags at all.')));
    }

    public function test_converting_empty_html_gives_an_empty_document(): void
    {
        $this->assertTrue(TipTap::isEmpty(HtmlToTipTap::convert('')));
        $this->assertTrue(TipTap::isEmpty(HtmlToTipTap::convert(null)));
        $this->assertTrue(TipTap::isEmpty(HtmlToTipTap::convert('<p></p>')));
    }

    public function test_a_bold_lead_in_that_runs_into_its_sentence_gets_its_space_back(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('Cut the steak.', [['type' => 'bold']]),
            $this->text('Dice the sirloin.'),
        )));

        $this->assertSame('<p><strong>Cut the steak.</strong> Dice the sirloin.</p>', $html);
    }

    public function test_a_space_that_is_already_there_is_not_doubled(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('Cut the steak.', [['type' => 'bold']]),
            $this->text(' Dice the sirloin.'),
        )));

        $this->assertSame('<p><strong>Cut the steak.</strong> Dice the sirloin.</p>', $html);
    }

    public function test_bold_in_the_middle_of_a_word_is_left_alone(): void
    {
        // No sentence ending, so nothing is inserted.
        $html = TipTap::html($this->doc($this->para(
            $this->text('un', [['type' => 'bold']]),
            $this->text('likely'),
        )));

        $this->assertSame('<p><strong>un</strong>likely</p>', $html);
    }

    public function test_an_unmarked_run_before_is_left_alone(): void
    {
        // Two plain runs that happen to be split are not a lead-in.
        $html = TipTap::html($this->doc($this->para(
            $this->text('Step one.'),
            $this->text('Step two.'),
        )));

        $this->assertSame('<p>Step one.Step two.</p>', $html);
    }

    public function test_punctuation_that_does_not_end_a_sentence_is_left_alone(): void
    {
        $html = TipTap::html($this->doc($this->para(
            $this->text('pre', [['type' => 'bold']]),
            $this->text('heat'),
        )));

        $this->assertSame('<p><strong>pre</strong>heat</p>', $html);
    }
}
