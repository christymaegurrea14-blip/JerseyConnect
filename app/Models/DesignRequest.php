<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'template_id',
    'template_name',
    'template_image',
    'template_price',
    'team_name',
    'primary_color',
    'secondary_color',
    'accent_color',
    'font_style',
    'estimated_quantity',
    'notes',
    'logo_path',
    'gcash_number',
    'reference_number',
    'proof_image',
    'status',
])]
class DesignRequest extends Model
{
    use HasFactory;

    protected $casts = [
        'template_price'      => 'integer',
        'estimated_quantity'  => 'integer',
    ];

    protected $appends = ['logo_url', 'proof_image_url', 'template_image_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getProofImageUrlAttribute(): ?string
    {
        return $this->proof_image ? Storage::disk('public')->url($this->proof_image) : null;
    }

    public function getTemplateImageUrlAttribute(): ?string
    {
        return $this->template_image ? Storage::disk('public')->url($this->template_image) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Jersey::class, 'template_id');
    }

    protected function serializeDate($date)
    {
        return $date->toIso8601String();
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function messageThread(): HasOne
    {
        return $this->hasOne(MessageThread::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(DesignRequestPlayer::class)->orderBy('number');
    }

    protected static function booted(): void
    {
        static::created(function (DesignRequest $designRequest) {
            $designRequest->messageThread()->create([]);
        });
    }
}
