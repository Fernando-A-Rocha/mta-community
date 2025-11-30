<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    public const TYPE_RESOURCE = Resource::class;

    public const TYPE_USER = User::class;

    public const COMMENT_MIN_LENGTH = 60;

    /**
     * @var array<string, string>
     */
    public const RESOURCE_REASONS = [
        'spam_fraud' => 'Spam or fraud',
        'malware' => 'Malware or suspicious files',
        'copyright' => 'Copyright or license violation',
        'misleading' => 'Misleading or abusive content',
        'security' => 'Security vulnerability / backdoor',
        'other' => 'Other (explain below)',
    ];

    /**
     * @var array<string, string>
     */
    public const USER_REASONS = [
        'impersonation' => 'Impersonation or identity abuse',
        'harassment' => 'Harassment, abusive language or threats',
        'spam' => 'Spam, scams or advertising',
        'cheating' => 'Cheating, fraud or exploits',
        'other' => 'Other (explain below)',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'comment',
        'status',
        'handled_by_id',
        'handled_at',
    ];

    protected $casts = [
        'status' => ReportStatus::class,
        'handled_at' => 'datetime',
    ];

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }

    /**
     * @return array<string, string>
     */
    public static function reasonOptionsFor(string $reportableType): array
    {
        return $reportableType === self::TYPE_RESOURCE ? self::RESOURCE_REASONS : self::USER_REASONS;
    }

    public function reasonLabel(): string
    {
        $options = $this->reasonOptionsFor($this->reportable_type);

        return $options[$this->reason] ?? Str::title(str_replace('_', ' ', $this->reason));
    }

    public function typeLabel(): string
    {
        return $this->reportable_type === self::TYPE_RESOURCE ? __('Resource') : __('User');
    }
}
