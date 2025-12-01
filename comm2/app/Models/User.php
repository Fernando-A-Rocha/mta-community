<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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
        'avatar_path',
        'favorite_city',
        'favorite_vehicle',
        'favorite_character',
        'favorite_gang',
        'favorite_weapon',
        'favorite_radio_station',
        'allow_friend_requests',
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
            'allow_friend_requests' => 'boolean',
        ];
    }

    /**
     * Get the user's initials (fallback when no avatar)
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
     * Get the user's avatar URL
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    /**
     * Check if user has an avatar
     */
    public function hasAvatar(): bool
    {
        return ! empty($this->avatar_path) && Storage::disk('public')->exists($this->avatar_path);
    }

    /**
     * Get the resources created by this user
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function submittedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function receivedReports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
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

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function followedResources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_follows')->withTimestamps();
    }

    public function followedUsers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'follower_id', 'followed_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'followed_id', 'follower_id')->withTimestamps();
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    public function friendshipWith(User $user): ?Friendship
    {
        if ($user->id === $this->id) {
            return null;
        }

        return Friendship::between($this->id, $user->id)->first();
    }

    public function isFriendsWith(User $user): bool
    {
        $friendship = $this->friendshipWith($user);

        return $friendship?->status === FriendshipStatus::Accepted;
    }

    public function hasPendingFriendRequestWith(User $user): bool
    {
        $friendship = $this->friendshipWith($user);

        return $friendship?->status === FriendshipStatus::Pending;
    }

    public function isFollowingResource(Resource $resource): bool
    {
        return $this->followedResources()
            ->where('resources.id', $resource->id)
            ->exists();
    }

    public function isFollowingUser(User $user): bool
    {
        if ($user->id === $this->id) {
            return false;
        }

        return $this->followedUsers()
            ->where('followed_id', $user->id)
            ->exists();
    }
}
