<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_visibility',
        'role',
        'favorite_city',
        'favorite_vehicle',
        'favorite_character',
        'favorite_gang',
        'favorite_weapon',
        'favorite_radio_station',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'old_password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the resources created by this user
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a moderator or admin
     */
    public function isModerator(): bool
    {
        return in_array($this->role, ['moderator', 'admin'], true);
    }

    /**
     * Check if user has at least moderator permissions
     */
    public function canModerate(): bool
    {
        return $this->isModerator();
    }

    /**
     * Get the role's display name and badge color classes
     *
     * @return array{name: string, color: string}|null
     */
    public function roleBadge(): ?array
    {
        return match ($this->role) {
            'admin' => [
                'name' => __('Admin'),
                'color' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            ],
            'moderator' => [
                'name' => __('Moderator'),
                'color' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            ],
            default => null,
        };
    }
}
