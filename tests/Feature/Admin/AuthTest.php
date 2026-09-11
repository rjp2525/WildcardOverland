<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_the_login_screen_renders(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/Login'));
    }

    public function test_a_user_can_sign_in(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_bad_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_user_can_sign_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_signed_in_users_are_sent_away_from_the_login_screen(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
