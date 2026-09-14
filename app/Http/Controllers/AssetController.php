<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetFileRequest;
use App\Services\ImageRenderer;
use App\Support\AssetSignature;
use App\Support\ImageVariant;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Serves image derivatives.
 *
 * Nothing under storage/ is reachable by the web server, so every image goes
 * through here. The signature is what makes that safe to expose: it ties the
 * path, the variant and the width together, so the only renderings anyone
 * can ask for are the ones the application itself linked to.
 */
class AssetController extends Controller
{
    public function __construct(protected ImageRenderer $renderer) {}

    public function __invoke(AssetFileRequest $request, string $path): Response
    {
        abort_unless(AssetSignature::verify("/assets/{$path}", $request->all()), 403);

        $variant = ImageVariant::make($request->string('v')->value());
        $width = $request->integer('w');

        // The signature covers this pair, so a mismatch means the variant
        // table changed under a URL that is still in someone's cache.
        abort_unless($variant->allows($width), 404);

        try {
            ['bytes' => $bytes, 'mime' => $mime] = $this->renderer->render($path, $variant, $width);
        } catch (Throwable $e) {
            /*
             * A source that is not on the disk, an unreadable file and a
             * missing image driver all arrive here and all leave as the same
             * bare 404. Record why first, otherwise a broken image is
             * indistinguishable from a wrong URL.
             */
            Log::warning('Asset could not be rendered', [
                'path' => $path,
                'variant' => $variant->name,
                'width' => $width,
                'disk' => config('assets.disk'),
                'error' => $e->getMessage(),
            ]);

            abort(404);
        }

        return response($bytes, 200, [
            'Content-Type' => $mime,
            // Derivatives are immutable: a replaced source is written to a
            // new path, so the URL changes with the bytes.
            'Cache-Control' => 'public, max-age='.config('assets.max_age').', immutable',
            ...$this->hardening($mime),
        ]);
    }

    /**
     * An SVG is a document, so opening one of these URLs directly renders it
     * as a page on our own origin. The bytes were already sanitised on
     * upload; this makes sure that even a hole in that cannot reach out,
     * load anything or run anything.
     *
     * @return array<string, string>
     */
    protected function hardening(string $mime): array
    {
        if ($mime !== 'image/svg+xml') {
            return [];
        }

        return [
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ];
    }
}
