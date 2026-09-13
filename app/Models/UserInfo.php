<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

#[Unguarded('*')]
class UserInfo extends Model
{
    use HasFactory;

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
