<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
        'responded_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'responded_at' => 'datetime',
        'status' => FriendshipStatus::class,
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    public function scopeBetween($query, int $firstUserId, int $secondUserId)
    {
        return $query->where(function ($inner) use ($firstUserId, $secondUserId) {
            $inner->where('requester_id', $firstUserId)
                ->where('addressee_id', $secondUserId);
        })->orWhere(function ($inner) use ($firstUserId, $secondUserId) {
            $inner->where('requester_id', $secondUserId)
                ->where('addressee_id', $firstUserId);
        });
    }

    public function involvesUser(int $userId): bool
    {
        return $this->requester_id === $userId || $this->addressee_id === $userId;
    }

    public function otherParty(int $userId): ?User
    {
        if (! $this->involvesUser($userId)) {
            return null;
        }

        return $this->requester_id === $userId ? $this->addressee : $this->requester;
    }

    public function isPending(): bool
    {
        return $this->status === FriendshipStatus::Pending;
    }

    public function isAccepted(): bool
    {
        return $this->status === FriendshipStatus::Accepted;
    }
}
