<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds nothing on purpose.
     *
     * The admin routes are gated on being signed in and nothing else, so any
     * user row is a full administrator. The scaffold that used to sit here
     * created one with the factory's password, which meant a stray `db:seed`
     * handed out admin access on whatever environment it ran against.
     *
     * Accounts are made deliberately, one at a time:
     *
     *     php artisan admin:create you@example.com --generate
     *
     * Content lives in PartnerSeeder and DemoContentSeeder, which are called
     * by name rather than from here.
     */
    public function run(): void
    {
        $this->command?->warn('Nothing to seed. Use admin:create for an account, or call a seeder by name.');
    }
}
