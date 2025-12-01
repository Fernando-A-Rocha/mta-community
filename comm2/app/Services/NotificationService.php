<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * @param  User|Collection<int, User>|iterable<int, User>|int|null  $recipients
     */
    public function notify(
        User|Collection|iterable|int|null $recipients,
        NotificationCategory $category,
        string $title,
        string $body,
        array $payload = [],
        ?string $actionUrl = null,
    ): void {
        $users = $this->normalizeRecipients($recipients);

        if ($users->isEmpty()) {
            return;
        }

        $timestamp = now();

        $rows = $users->map(function (int $userId) use ($category, $title, $body, $payload, $actionUrl, $timestamp) {
            return [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'category' => $category->value,
                'title' => $title,
                'body' => $body,
                'payload' => empty($payload) ? null : $payload,
                'action_url' => $actionUrl,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();

        Notification::insert($rows);
    }

    /**
     * @param  User|Collection<int, User>|iterable<int, User>|int|null  $recipients
     */
    private function normalizeRecipients(User|Collection|iterable|int|null $recipients): Collection
    {
        $collection = collect();

        if ($recipients === null) {
            return $collection;
        }

        if ($recipients instanceof User) {
            $collection->push($recipients->id);
        } elseif ($recipients instanceof Collection) {
            $collection = $recipients->pluck('id');
        } elseif (is_int($recipients)) {
            $collection->push($recipients);
        } else {
            foreach ($recipients as $recipient) {
                if ($recipient instanceof User) {
                    $collection->push($recipient->id);
                } elseif (is_array($recipient) && isset($recipient['id'])) {
                    $collection->push((int) $recipient['id']);
                } elseif (is_int($recipient)) {
                    $collection->push($recipient);
                }
            }
        }

        return $collection
            ->filter(fn ($id) => ! empty($id))
            ->unique()
            ->values();
    }
}
