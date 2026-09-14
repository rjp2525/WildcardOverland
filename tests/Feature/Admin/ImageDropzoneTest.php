<?php

namespace Tests\Feature\Admin;

use App\Enums\ImageType;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The endpoint the admin dropzones post to. It has to answer with the new
 * image rather than a redirect, because it is called from inside a form that
 * is halfway filled in and must not navigate.
 */
class ImageDropzoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assets.disk' => 'uploads-test']);
        Storage::fake('uploads-test');
    }

    public function test_it_returns_the_new_image_as_a_pickable_option(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('admin.images.upload'), [
            'file' => UploadedFile::fake()->image('camp-stove.jpg', 800, 600),
        ]);

        $image = Image::sole();

        $response->assertOk()->assertJson([
            'value' => $image->id,
            'label' => $image->name ?: "Image #{$image->id}",
        ]);

        $this->assertNotNull($response->json('thumb'));
    }

    public function test_a_logo_is_typed_as_a_logo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('admin.images.upload'), [
            'file' => UploadedFile::fake()->image('brand.png', 400, 200),
            'image_type' => ImageType::Logo->value,
        ])->assertOk();

        $this->assertSame(ImageType::Logo, Image::sole()->type);
    }

    public function test_it_rejects_something_that_is_not_an_image(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('admin.images.upload'), [
            'file' => UploadedFile::fake()->create('notes.pdf', 12, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('file');

        $this->assertSame(0, Image::count());
    }

    public function test_it_is_behind_the_login(): void
    {
        $this->postJson(route('admin.images.upload'), [
            'file' => UploadedFile::fake()->image('sneaky.jpg'),
        ])->assertUnauthorized();

        $this->assertSame(0, Image::count());
    }
}
