<?php

namespace Tests\Feature;

use App\Models\File;
use App\Services\FileUploadService;
use App\Support\AssetUrl;
use Database\Seeders\Demo\DemoImageFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A File record is enough on its own to build a signed URL, so a record whose
 * bytes are not on the disk being served from produces a perfectly valid link
 * to a 404. These cover the drift and the recovery from it.
 */
class AssetServingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'assets-test']);
        Storage::fake('assets-test');
        Storage::fake('other-disk');
    }

    protected function upload(string $key = 'probe'): File
    {
        $path = tempnam(sys_get_temp_dir(), 'asset').'.png';
        file_put_contents($path, (new DemoImageFactory)->png($key));

        return app(FileUploadService::class)->store(
            new UploadedFile($path, "{$key}.png", 'image/png', null, true),
        );
    }

    protected function urlFor(File $file): string
    {
        return AssetUrl::image($file, 600, 600, 'jpg', 80, 'crop-center');
    }

    public function test_a_stored_image_is_served(): void
    {
        $this->get($this->urlFor($this->upload()))->assertOk();
    }

    public function test_a_tampered_signature_is_forbidden_not_missing(): void
    {
        $url = $this->urlFor($this->upload());

        $this->get(preg_replace('/&s=.*/', '&s=deadbeef', $url))->assertForbidden();
    }

    public function test_a_record_without_its_object_serves_a_404(): void
    {
        $file = $this->upload();
        Storage::disk('assets-test')->delete($file->stored_path);

        $this->get($this->urlFor($file))->assertNotFound();
    }

    public function test_uploading_the_same_bytes_again_puts_a_missing_object_back(): void
    {
        $file = $this->upload();
        $originalPath = $file->stored_path;
        Storage::disk('assets-test')->delete($originalPath);

        $again = $this->upload();

        $this->assertTrue($again->is($file), 'The record should be reused, not duplicated.');
        $this->assertNotSame($originalPath, $again->stored_path, 'A fresh path avoids a stale Glide cache.');
        Storage::disk('assets-test')->assertExists($again->stored_path);

        $this->get($this->urlFor($again->fresh()))->assertOk();
    }

    public function test_a_record_left_on_another_disk_is_moved_to_the_current_one(): void
    {
        $file = $this->upload();
        $file->update(['disk' => 'other-disk']);

        $again = $this->upload();

        $this->assertTrue($again->is($file));
        $this->assertSame('assets-test', $again->disk);
        Storage::disk('assets-test')->assertExists($again->stored_path);
    }

    public function test_an_intact_upload_is_not_rewritten(): void
    {
        $file = $this->upload();
        $path = $file->stored_path;

        $this->assertSame($path, $this->upload()->stored_path);
        $this->assertSame(1, File::count());
    }

    public function test_the_check_command_names_the_files_that_are_missing(): void
    {
        $intact = $this->upload('intact');
        $broken = $this->upload('broken');
        Storage::disk('assets-test')->delete($broken->stored_path);

        $this->artisan('assets:check')
            ->expectsOutputToContain('Missing from the disk')
            ->assertExitCode(1);

        Storage::disk('assets-test')->assertExists($intact->stored_path);
    }

    public function test_the_check_command_is_quiet_when_everything_is_present(): void
    {
        $this->upload();

        $this->artisan('assets:check')
            ->expectsOutputToContain('Every file is where it should be.')
            ->assertExitCode(0);
    }
}
