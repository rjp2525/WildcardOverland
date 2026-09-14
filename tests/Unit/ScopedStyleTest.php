<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards against a Vue single file component leaking a style onto the whole
 * document.
 *
 * This is not hypothetical. `:global(.dark) .partner-logo :deep(img)`
 * compiled down to `.dark` on its own, and since `.dark` sits on <html> that
 * put `filter: brightness(0) invert()` over every pixel of the site. The
 * page looked like a flat grey rectangle while the DOM inspected perfectly
 * fine, which is a miserable thing to debug.
 */
class ScopedStyleTest extends TestCase
{
    /**
     * @return list<string>
     */
    protected function components(): array
    {
        $found = [];
        $base = dirname(__DIR__, 2).'/resources/js';

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'vue') {
                $found[] = $file->getPathname();
            }
        }

        return $found;
    }

    /**
     * The component's style blocks, with comments taken out so that writing
     * about a selector is not mistaken for using one.
     */
    protected function styles(string $path): string
    {
        preg_match_all('/<style[^>]*>(.*?)<\/style>/s', (string) file_get_contents($path), $blocks);

        return preg_replace('/\/\*.*?\*\//s', '', implode("\n", $blocks[1])) ?? '';
    }

    public function test_no_component_combines_global_with_a_descendant(): void
    {
        $offenders = [];

        foreach ($this->components() as $path) {
            /*
             * `:global(.foo) { }` on its own is fine. It is only the
             * combined form that misbehaves, where the compiler keeps the
             * global part and throws the rest of the selector away.
             */
            if (preg_match('/:global\([^)]*\)\s*[^\s{,][^{,]*[,{]/', $this->styles($path))) {
                $offenders[] = str_replace(dirname(__DIR__, 2).'/', '', $path);
            }
        }

        $this->assertSame([], $offenders, implode("\n", [
            'These combine :global() with a descendant selector, which Vue',
            'compiles down to the :global() part alone. Write the ancestor',
            'class plainly instead, for example ".dark .thing", which stays',
            'correctly scoped:',
            ...$offenders,
        ]));
    }

    public function test_the_built_css_never_targets_the_theme_class_on_its_own(): void
    {
        $sheets = glob(dirname(__DIR__, 2).'/public/build/assets/*.css') ?: [];

        if ($sheets === []) {
            $this->markTestSkipped('No build to check. Run the asset build first.');
        }

        $offenders = [];

        foreach ($sheets as $sheet) {
            $css = (string) file_get_contents($sheet);

            // A rule whose whole selector is `.dark`, which is on <html>.
            if (preg_match_all('/(?:^|[};])\s*\.dark\s*\{([^}]*)\}/', $css, $matches)) {
                foreach ($matches[1] as $body) {
                    $offenders[] = basename($sheet).': .dark {'.trim($body).'}';
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", [
            'A rule targets .dark on its own, which is the class on <html>,',
            'so it applies to the entire document rather than to whatever it',
            'was meant for:',
            ...$offenders,
        ]));
    }
}
