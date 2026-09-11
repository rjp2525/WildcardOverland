<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\File;
use App\Models\Image;
use App\Models\NavigationLink;
use App\Models\User;
use App\Models\VehicleModification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /**
     * Every admin index screen should render its Inertia page.
     */
    public function test_each_index_screen_renders(): void
    {
        $screens = [
            'admin.dashboard' => 'admin/Dashboard',
            'admin.trips.index' => 'admin/trips/Index',
            'admin.vehicle-modifications.index' => 'admin/vehicle-modifications/Index',
            'admin.brands.index' => 'admin/brands/Index',
            'admin.images.index' => 'admin/images/Index',
            'admin.files.index' => 'admin/files/Index',
            'admin.navigation-links.index' => 'admin/navigation-links/Index',
        ];

        foreach ($screens as $routeName => $component) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component($component));
        }
    }

    public function test_a_modification_cost_round_trips_between_dollars_and_cents(): void
    {
        $this->post(route('admin.vehicle-modifications.store'), [
            'name' => 'Roof Rack',
            'vendor' => 'Prinsu',
            'cost' => '1249.99',
        ])->assertRedirect();

        $mod = VehicleModification::sole();

        // Stored as integer cents...
        $this->assertSame(124999, $mod->cost);

        // ...and presented back as dollars.
        $this->get(route('admin.vehicle-modifications.edit', $mod))
            ->assertInertia(fn ($page) => $page->where('modification.cost', '1249.99'));
    }

    public function test_a_blank_modification_cost_stays_null(): void
    {
        $this->post(route('admin.vehicle-modifications.store'), ['name' => 'Sticker'])
            ->assertRedirect();

        $this->assertNull(VehicleModification::sole()->cost);
    }

    public function test_brand_colors_must_be_hex(): void
    {
        $this->post(route('admin.brands.store'), [
            'name' => 'Bad',
            'primary_color' => 'not-a-color',
        ])->assertSessionHasErrors('primary_color');

        $this->assertSame(0, Brand::count());
    }

    public function test_a_brand_resolves_its_logo_through_to_the_file(): void
    {
        $file = File::create([
            'name' => 'Logo', 'original_filename' => 'logo.png', 'original_extension' => 'png',
            'mime' => 'image/png', 'hash' => str_repeat('a', 64), 'type' => 'content',
            'size' => 2048, 'stored_path' => 'uploads/logo.png', 'disk' => 'local',
        ]);
        $image = Image::create(['name' => 'Logo', 'file_id' => $file->id, 'width' => 8, 'height' => 8]);

        $this->post(route('admin.brands.store'), [
            'name' => 'Katadyn',
            'logo_image_id' => $image->id,
            'website' => 'https://katadyn.com',
        ])->assertRedirect();

        $this->assertSame('logo.png', Brand::sole()->logo->file->original_filename);
    }

    public function test_a_navigation_link_must_point_at_a_real_route(): void
    {
        $this->post(route('admin.navigation-links.store'), [
            'name' => 'Nowhere',
            'route_name' => 'route.that.does.not.exist',
        ])->assertSessionHasErrors('route_name');

        $this->post(route('admin.navigation-links.store'), [
            'name' => 'About',
            'route_name' => 'about',
        ])->assertSessionHasNoErrors();

        $this->assertSame('about', NavigationLink::sole()->route_name);
    }

    public function test_a_navigation_link_cannot_be_its_own_parent(): void
    {
        $link = NavigationLink::create(['name' => 'About', 'route_name' => 'about']);

        $this->put(route('admin.navigation-links.update', $link), [
            'name' => 'About',
            'route_name' => 'about',
            'parent_id' => $link->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_removing_an_image_keeps_the_underlying_file(): void
    {
        $file = File::create([
            'name' => 'Logo', 'original_filename' => 'logo.png', 'original_extension' => 'png',
            'mime' => 'image/png', 'hash' => str_repeat('b', 64), 'type' => 'content',
            'size' => 2048, 'stored_path' => 'uploads/logo.png', 'disk' => 'local',
        ]);
        $image = Image::create(['file_id' => $file->id]);

        $this->delete(route('admin.images.destroy', $image))->assertRedirect();

        $this->assertSame(0, Image::count());
        $this->assertSame(1, File::count());
    }

    public function test_search_filters_the_index(): void
    {
        VehicleModification::create(['name' => 'Roof Rack']);
        VehicleModification::create(['name' => 'Winch']);

        $this->get(route('admin.vehicle-modifications.index', ['search' => 'winch']))
            ->assertInertia(fn ($page) => $page
                ->has('modifications.data', 1)
                ->where('modifications.data.0.name', 'Winch'));
    }
}
