<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\PartnerSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;

/**
 * One command to bring a fresh deployment up: migrate, seed the partners,
 * optionally seed placeholder content, and make sure an admin can log in.
 *
 * Every step is idempotent, so it is safe to re-run.
 */
class SiteSetup extends Command
{
    protected $signature = 'site:setup
                            {--demo : Also seed placeholder trips, recipes and photos}
                            {--admin-email= : Create or update an admin with this email}
                            {--admin-name=Reno : Name for that admin}
                            {--admin-password= : Password for that admin (prompted if omitted)}
                            {--geocode : Resolve campsite coordinates to states afterwards}';

    protected $description = 'Prepare the site: migrate, seed partners, optionally seed demo content and an admin';

    public function handle(): int
    {
        $this->components->info('Preparing Wildcard Overland');

        $this->components->task('Running migrations', function () {
            $this->callSilent('migrate', ['--force' => true]);

            return true;
        });

        $this->components->task('Seeding partners', function () {
            $this->callSilent('db:seed', ['--class' => PartnerSeeder::class, '--force' => true]);

            return true;
        });

        if ($this->option('demo')) {
            $this->newLine();
            $this->components->warn('Seeding placeholder content. Remove it later with:');
            $this->components->bulletList(['php artisan db:seed --class=DemoContentCleanupSeeder']);

            $this->call('db:seed', ['--class' => DemoContentSeeder::class, '--force' => true]);
        }

        if ($email = $this->option('admin-email')) {
            $this->createAdmin($email);
        }

        if ($this->option('geocode')) {
            $this->newLine();
            $this->call('campsites:geocode');
        }

        $this->newLine();
        $this->components->info('Done. Sign in at '.rtrim((string) config('app.url'), '/').'/admin');

        return self::SUCCESS;
    }

    protected function createAdmin(string $email): void
    {
        $existing = User::firstWhere('email', $email);

        $plain = $this->option('admin-password')
            ?: ($this->input->isInteractive()
                ? password('Password for '.$email, required: true)
                : null);

        if ($existing && $plain === null) {
            $this->components->info("Admin {$email} already exists; password left unchanged.");

            return;
        }

        if ($plain === null) {
            $this->components->error(
                'No password given. Pass --admin-password, or run interactively.'
            );

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => $this->option('admin-name'), 'password' => Hash::make($plain)],
        );

        $this->components->info(($existing ? 'Updated' : 'Created')." admin {$email}.");
    }
}
