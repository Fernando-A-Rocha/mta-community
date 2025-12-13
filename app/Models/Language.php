<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_language');
    }
}
