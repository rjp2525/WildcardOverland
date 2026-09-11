<?php

namespace Tests\Feature\Admin;

use App\Enums\ImageType;
use App\Models\File;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'uploads-test']);
        Storage::fake('uploads-test');

        $this->actingAs(User::factory()->create());
    }

    public function test_uploading_an_image_records_a_file_and_an_image(): void
    {
        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->image('logo.png', 120, 60),
            'type' => 'content',
        ])->assertRedirect();

        $file = File::sole();

        $this->assertSame('logo.png', $file->original_filename);
        $this->assertSame('content', $file->type);
        $this->assertSame('uploads-test', $file->disk);
        $this->assertNotNull($file->hash);
        Storage::disk('uploads-test')->assertExists($file->stored_path);

        $image = Image::sole();
        $this->assertSame($file->id, $image->file_id);
        $this->assertSame(120, $image->width);
        $this->assertSame(60, $image->height);
    }

    public function test_an_upload_records_the_chosen_image_type(): void
    {
        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->image('mark.png'),
            'image_type' => ImageType::Logo->value,
        ])->assertRedirect();

        $this->assertSame(ImageType::Logo, Image::sole()->type);
    }

    public function test_uploads_default_to_photographs(): void
    {
        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->image('sunset.png'),
        ])->assertRedirect();

        $this->assertSame(ImageType::Photo, Image::sole()->type);
    }

    public function test_an_unknown_image_type_is_rejected(): void
    {
        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->image('sunset.png'),
            'image_type' => 'banana',
        ])->assertSessionHasErrors('image_type');

        $this->assertSame(0, File::count());
    }

    public function test_an_image_type_can_be_changed_afterwards(): void
    {
        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->image('mark.png'),
        ]);

        $image = Image::sole();

        $this->put(route('admin.images.update', $image), [
            'name' => 'Partner mark',
            'type' => ImageType::Logo->value,
        ])->assertRedirect();

        $this->assertSame(ImageType::Logo, $image->fresh()->type);
    }

    public function test_a_non_image_does_not_create_an_image_record(): void
    {
        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(1, File::count());
        $this->assertSame(0, Image::count());
    }

    public function test_identical_uploads_are_deduplicated_by_hash(): void
    {
        $make = fn () => UploadedFile::fake()->createWithContent('same.txt', 'identical bytes');

        $this->post(route('admin.files.store'), ['file' => $make()])->assertRedirect();
        $this->post(route('admin.files.store'), ['file' => $make()])->assertRedirect();

        $this->assertSame(1, File::count());
    }

    public function test_deleting_a_file_removes_it_from_the_disk(): void
    {
        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->image('logo.png'),
        ]);

        $file = File::sole();
        $path = $file->stored_path;

        $this->delete(route('admin.files.destroy', $file))->assertRedirect();

        Storage::disk('uploads-test')->assertMissing($path);
        $this->assertSame(0, File::withTrashed()->count());
        $this->assertSame(0, Image::count());
    }

    public function test_oversized_uploads_are_rejected(): void
    {
        config(['assets.max_upload_kb' => 10]);

        $this->post(route('admin.files.store'), [
            'file' => UploadedFile::fake()->create('big.bin', 50),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, File::count());
    }
}
