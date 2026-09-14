<?php

namespace Tests\Feature\Admin;

use App\Enums\ImageType;
use App\Models\File;
use App\Models\Image;
use App\Models\User;
use App\Support\AssetUrl;
use App\Support\ImagePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A brand logo really wants to be an SVG. They skip the resizing pipeline
 * entirely, so the whole job is making sure the bytes are safe and that
 * nothing downstream tries to treat them as pixels.
 */
class SvgUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'uploads-test']);
        Storage::fake('uploads-test');

        $this->actingAs(User::factory()->create());
    }

    protected function svg(string $body, string $root = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 80">'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'svg').'.svg';
        file_put_contents($path, $root.$body.'</svg>');

        return new UploadedFile($path, 'logo.svg', 'image/svg+xml', null, true);
    }

    protected function upload(UploadedFile $file)
    {
        return $this->postJson(route('admin.images.upload'), [
            'file' => $file,
            'image_type' => ImageType::Logo->value,
        ]);
    }

    public function test_an_svg_is_accepted(): void
    {
        $this->upload($this->svg('<rect width="240" height="80" fill="#e85a2f"/>'))
            ->assertOk();

        $file = File::sole();

        $this->assertSame('image/svg+xml', $file->mime);
        $this->assertTrue($file->isVector());
        Storage::disk('uploads-test')->assertExists($file->stored_path);
    }

    public function test_a_script_never_reaches_the_disk(): void
    {
        $this->upload($this->svg(
            '<script>alert(document.cookie)</script>'
            .'<rect width="240" height="80" onload="alert(1)" fill="#000"/>'
        ))->assertOk();

        $stored = Storage::disk('uploads-test')->get(File::sole()->stored_path);

        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onload', $stored);
        $this->assertStringNotContainsString('alert', $stored);
        $this->assertStringContainsString('<rect', $stored);
    }

    public function test_a_reference_out_to_another_server_is_stripped(): void
    {
        $this->upload($this->svg('<image href="https://example.test/tracker.png" />'))
            ->assertOk();

        $stored = Storage::disk('uploads-test')->get(File::sole()->stored_path);

        $this->assertStringNotContainsString('example.test', $stored);
    }

    public function test_the_size_comes_from_the_view_box(): void
    {
        $this->upload($this->svg('<rect width="240" height="80"/>'))->assertOk();

        $image = Image::sole();

        $this->assertSame(240, $image->width);
        $this->assertSame(80, $image->height);
        // Nothing to average, and a tinted placeholder behind a transparent
        // logo looks worse than none at all.
        $this->assertNull($image->dominant_color);
    }

    public function test_explicit_dimensions_win_over_the_view_box(): void
    {
        $this->upload($this->svg(
            '<rect width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="512px" height="128px" viewBox="0 0 240 80">'
        ))->assertOk();

        $this->assertSame(512, Image::sole()->width);
        $this->assertSame(128, Image::sole()->height);
    }

    public function test_something_that_is_not_svg_at_all_is_refused(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'svg').'.svg';
        file_put_contents($path, 'this is not markup');

        $this->upload(new UploadedFile($path, 'broken.svg', 'image/svg+xml', null, true))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertSame(0, File::count());
    }

    public function test_it_is_served_back_untouched_and_locked_down(): void
    {
        $this->upload($this->svg('<rect width="240" height="80" fill="#e85a2f"/>'))->assertOk();

        $file = File::sole();
        $stored = Storage::disk('uploads-test')->get($file->stored_path);

        $response = $this->get(AssetUrl::image($file, 'logo', 160));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
        $this->assertSame($stored, $response->getContent());
    }

    public function test_it_is_rendered_without_a_srcset(): void
    {
        $this->upload($this->svg('<rect width="240" height="80"/>'))->assertOk();

        $presented = ImagePresenter::contain(Image::sole());

        $this->assertSame('', $presented['srcset']);
        // Its own shape, not the variant's box.
        $this->assertSame(240, $presented['width']);
        $this->assertSame(80, $presented['height']);
    }

    public function test_a_png_still_gets_the_full_ladder(): void
    {
        $this->postJson(route('admin.images.upload'), [
            'file' => UploadedFile::fake()->image('photo.jpg', 1600, 900),
        ])->assertOk();

        $presented = ImagePresenter::card(Image::sole());

        $this->assertStringContainsString('640w', $presented['srcset']);
        $this->assertNotSame('', $presented['srcset']);
    }

    public function test_a_doctype_declaring_an_entity_reads_nothing_off_the_disk(): void
    {
        $secret = tempnam(sys_get_temp_dir(), 'secret');
        file_put_contents($secret, 'TOP-SECRET-VALUE');

        $path = tempnam(sys_get_temp_dir(), 'svg').'.svg';
        file_put_contents($path, <<<XML
            <?xml version="1.0"?>
            <!DOCTYPE svg [<!ENTITY xxe SYSTEM "file://{$secret}">]>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><text>&xxe;</text></svg>
            XML);

        $response = $this->upload(new UploadedFile($path, 'xxe.svg', 'image/svg+xml', null, true));

        if ($response->status() === 200) {
            $stored = Storage::disk('uploads-test')->get(File::sole()->stored_path);
            $this->assertStringNotContainsString('TOP-SECRET-VALUE', $stored);
        } else {
            // Refusing it outright is just as good an answer.
            $response->assertStatus(422);
            $this->assertSame(0, File::count());
        }
    }

    public function test_an_illustrator_export_survives_intact(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'svg').'.svg';
        file_put_contents($path, <<<'XML'
            <?xml version="1.0" encoding="utf-8"?>
            <!-- Generator: Adobe Illustrator 27.0.0, SVG Export Plug-In -->
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 300 100">
            <path d="M10 10h80v80h-80z"/>
            </svg>
            XML);

        $this->upload(new UploadedFile($path, 'brand.svg', 'image/svg+xml', null, true))->assertOk();

        $this->assertStringContainsString('<path', Storage::disk('uploads-test')->get(File::sole()->stored_path));
        $this->assertSame(300, Image::sole()->width);
        $this->assertSame(100, Image::sole()->height);
    }
}
