<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationCategory: string
{
    case Resource = 'resource';
    case Friends = 'friends';
    case Reports = 'reports';

    public function label(): string
    {
        return match ($this) {
            self::Resource => __('Resources'),
            self::Friends => __('Friends'),
            self::Reports => __('Reports'),
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Resource => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            self::Friends => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            self::Reports => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        };
    }
}
