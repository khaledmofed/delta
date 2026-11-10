<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Create or update a staff account for the admin panel.
 * The password is asked interactively (hidden) or generated and printed once — never passed as an argument.
 */
class AdminUser extends Command
{
    protected $signature = 'admin:user {email} {--name= : Display name} {--role=super_admin : Role key from config/admin.php} {--generate : Generate a random password instead of asking}';

    protected $description = 'Create or update an admin panel user (staff).';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $role = $this->option('role');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }
        if (! array_key_exists($role, config('admin.roles'))) {
            $this->error('Unknown role. Use one of: '.implode(', ', array_keys(config('admin.roles'))));

            return self::FAILURE;
        }

        $generated = null;
        if ($this->option('generate') || ! $this->input->isInteractive()) {
            $password = $generated = Str::password(20);
        } else {
            $password = (string) $this->secret('Password (min. 12 characters)');
            if (strlen($password) < 12 || $password !== (string) $this->secret('Repeat password')) {
                $this->error('Passwords must match and be at least 12 characters.');

                return self::FAILURE;
            }
        }

        $user = User::firstOrNew(['email' => $email]);
        $created = ! $user->exists;
        $user->fill([
            'name' => $this->option('name') ?: ($user->name ?: Str::headline(Str::before($email, '@'))),
            'role' => $role,
            'password' => $password,
        ])->save();

        $this->info(($created ? 'Created' : 'Updated')." {$email} as ".config("admin.roles.{$role}.name").'.');
        if ($generated) {
            $this->warn("Password (shown once): {$generated}");
        }

        return self::SUCCESS;
    }
}
