<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Invalid = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Resolved => __('Resolved'),
            self::Invalid => __('Invalid'),
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            self::Resolved => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            self::Invalid => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
