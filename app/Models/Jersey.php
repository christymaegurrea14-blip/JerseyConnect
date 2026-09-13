<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jersey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'badge',
        'primary_color',
        'secondary_color',
        'accent_color',
        'sport',
        'status',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    protected function serializeDate($date)
    {
        return $date->toIso8601String();
    }
}
