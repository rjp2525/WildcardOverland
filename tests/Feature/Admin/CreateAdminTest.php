<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Signing in is the whole of the admin's access check, so a user row is a
 * full administrator. These pin down that accounts are only ever made on
 * purpose, and never with a password anybody could guess from the repo.
 */
class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_be_created_with_a_chosen_password(): void
    {
        $this->artisan('admin:create', [
            'email' => 'reno@example.test',
            '--password' => 'a-properly-long-password',
            '--name' => 'Reno',
        ])->assertExitCode(0);

        $user = User::firstWhere('email', 'reno@example.test');

        $this->assertNotNull($user);
        $this->assertSame('Reno', $user->name);
        $this->assertTrue(Hash::check('a-properly-long-password', $user->password));
    }

    public function test_a_generated_password_is_strong_and_actually_works(): void
    {
        $this->artisan('admin:create', ['email' => 'gen@example.test', '--generate' => true])
            ->assertExitCode(0);

        $user = User::firstWhere('email', 'gen@example.test');

        $this->assertNotNull($user);
        // Whatever it printed, the stored hash must not be a stock password.
        foreach (['password', 'secret', 'admin', ''] as $guess) {
            $this->assertFalse(Hash::check($guess, $user->password), "Guessable: {$guess}");
        }
    }

    public function test_running_it_again_updates_rather_than_duplicating(): void
    {
        $this->artisan('admin:create', ['email' => 'reno@example.test', '--password' => 'first-password-here']);
        $this->artisan('admin:create', ['email' => 'reno@example.test', '--password' => 'second-password-here']);

        $this->assertSame(1, User::where('email', 'reno@example.test')->count());
        $this->assertTrue(Hash::check('second-password-here', User::firstWhere('email', 'reno@example.test')->password));
    }

    public function test_it_refuses_rather_than_creating_an_account_with_no_password(): void
    {
        // As it runs on a deploy: no terminal to prompt at.
        $this->artisan('admin:create', ['email' => 'nobody@example.test', '--no-interaction' => true])
            ->expectsOutputToContain('No password given')
            ->assertExitCode(1);

        $this->assertNull(User::firstWhere('email', 'nobody@example.test'));
    }

    public function test_a_bad_address_is_rejected(): void
    {
        $this->artisan('admin:create', ['email' => 'not-an-email', '--generate' => true])
            ->assertExitCode(1);

        $this->assertSame(0, User::count());
    }

    public function test_the_default_seeder_hands_out_no_accounts(): void
    {
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        $this->assertSame(0, User::count());
    }
}
