<?php

namespace Tests\Feature;

use App\Models\File;
use App\Services\FileUploadService;
use App\Support\AssetUrl;
use App\Support\ImagePresenter;
use Database\Seeders\Demo\DemoImageFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResponsiveImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test', 'assets.cache_disk' => 'cache-test']);
        Storage::fake('assets-test');
        Storage::fake('cache-test');
    }

    protected function upload(string $key = 'probe'): File
    {
        $path = tempnam(sys_get_temp_dir(), 'asset').'.png';
        file_put_contents($path, (new DemoImageFactory)->png($key));

        return app(FileUploadService::class)->store(
            new UploadedFile($path, "{$key}.png", 'image/png', null, true),
        );
    }

    public function test_a_variant_is_rendered_as_webp_at_the_requested_width(): void
    {
        $file = $this->upload();

        $response = $this->get(AssetUrl::image($file, 'card', 480));

        $response->assertOk()->assertHeader('Content-Type', 'image/webp');

        [$width, $height] = getimagesizefromstring($response->getContent());

        $this->assertSame(480, $width);
        // The card variant is 3:2, so the height follows from the width.
        $this->assertSame(320, $height);
    }

    public function test_every_width_in_the_ladder_is_served(): void
    {
        $file = $this->upload();

        foreach (config('assets.variants.thumb.widths') as $width) {
            $response = $this->get(AssetUrl::image($file, 'thumb', $width));

            $response->assertOk();
            [$w, $h] = getimagesizefromstring($response->getContent());

            $this->assertSame([$width, $width], [$w, $h], "thumb at {$width} should be square");
        }
    }

    public function test_a_width_outside_the_variant_is_refused(): void
    {
        $file = $this->upload();

        // Signed for one width, requested at another.
        $url = str_replace('w=480', 'w=960', AssetUrl::image($file, 'card', 480));

        $this->get($url)->assertForbidden();
    }

    public function test_an_unknown_variant_is_rejected_before_anything_is_rendered(): void
    {
        $file = $this->upload();

        $url = str_replace('v=card', 'v=enormous', AssetUrl::image($file, 'card', 480));

        $this->get($url)->assertStatus(302);
    }

    public function test_a_derivative_is_only_rendered_once(): void
    {
        $file = $this->upload();
        $url = AssetUrl::image($file, 'thumb', 240);

        $this->get($url)->assertOk();
        $cached = Storage::disk('cache-test')->allFiles();

        $this->get($url)->assertOk();

        $this->assertCount(1, $cached);
        $this->assertSame($cached, Storage::disk('cache-test')->allFiles());
    }

    public function test_derivatives_are_cacheable_and_immutable(): void
    {
        $file = $this->upload();

        $this->get(AssetUrl::image($file, 'thumb', 240))
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    }

    public function test_the_presenter_emits_a_full_candidate_set(): void
    {
        $image = $this->upload()->image;

        $payload = ImagePresenter::card($image, 'A trip');

        $this->assertSame(960, $payload['width']);
        $this->assertSame(640, $payload['height']);
        $this->assertSame('A trip', $payload['alt']);
        $this->assertStringContainsString('(min-width: 1024px) 33vw', $payload['sizes']);

        foreach (config('assets.variants.card.widths') as $width) {
            $this->assertStringContainsString("{$width}w", $payload['srcset']);
        }
    }

    public function test_a_dominant_colour_is_sampled_for_the_placeholder(): void
    {
        $image = $this->upload()->image;

        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', (string) $image->dominant_color);
        $this->assertSame($image->dominant_color, ImagePresenter::card($image)['color']);
    }

    public function test_a_private_image_is_never_presented(): void
    {
        $image = $this->upload()->image;
        $image->update(['private' => true]);

        $this->assertNull(ImagePresenter::card($image->fresh()));
    }
}
