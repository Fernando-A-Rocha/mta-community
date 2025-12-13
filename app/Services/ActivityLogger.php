<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public static function log(string $action, ?User $user, ?string $ipAddress, array $context = [], ?string $userAgent = null): void
    {
        try {
            ActivityLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'context' => $context,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to persist activity log entry', [
                'action' => $action,
                'exception' => $exception,
            ]);
        }
    }
}
