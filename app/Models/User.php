<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Back-office staff (admin panel). Customers are stored in `customers`. */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Role permission check (config/admin.php). Used by the Gate definitions. */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, config("admin.roles.{$this->role}.permissions", []), true);
    }

    public function roleName(): string
    {
        return config("admin.roles.{$this->role}.name", $this->role);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1)) ?: 'U';
    }
}
