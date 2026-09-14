<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates the account that can sign in to the admin.
 *
 * There is no role column: the admin routes are gated on being signed in at
 * all, so every user row here is a full administrator. That is why there is
 * no seeded default account anywhere in this repo, and why this asks rather
 * than assuming.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
                            {email? : The address to sign in with}
                            {--name= : Display name}
                            {--password= : Set it directly, for scripts and CI}
                            {--generate : Use a strong random password and print it once}';

    protected $description = 'Create or update an administrator';

    public function handle(): int
    {
        $email = $this->argument('email')
            ?: ($this->input->isInteractive() ? text('Email address', required: true) : null);

        if ($email === null) {
            $this->components->error('No email given. Pass one as an argument.');

            return self::FAILURE;
        }

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->components->error("[{$email}] is not a valid email address.");

            return self::FAILURE;
        }

        $existing = User::firstWhere('email', $email);
        [$plain, $generated] = $this->resolvePassword();

        if ($plain === null) {
            $this->components->error(
                'No password given. Use --generate, pass --password, or run this interactively.'
            );

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $this->option('name') ?: ($existing->name ?? Str::before($email, '@')),
                'password' => Hash::make($plain),
            ],
        );

        $this->newLine();
        $this->components->info(($existing ? 'Updated' : 'Created')." administrator {$user->email}.");

        if ($generated) {
            $this->components->warn('This is shown once and is not stored anywhere in plain text:');
            $this->components->bulletList([$plain]);
        }

        $this->components->info('Sign in at '.rtrim((string) config('app.url'), '/').'/admin');

        return self::SUCCESS;
    }

    /**
     * @return array{0: string|null, 1: bool} the password, and whether it was generated
     */
    protected function resolvePassword(): array
    {
        if ($this->option('generate')) {
            return [Str::password(24), true];
        }

        if ($given = $this->option('password')) {
            return [$given, false];
        }

        if (! $this->input->isInteractive()) {
            return [null, false];
        }

        // Prompted rather than passed as an argument, so it stays out of the
        // shell history and off the process list.
        return [
            password(
                'Password',
                required: true,
                validate: fn (string $value) => Validator::make(
                    ['password' => $value],
                    ['password' => ['required', PasswordRule::min(12)]],
                )->errors()->first('password'),
            ),
            false,
        ];
    }
}
