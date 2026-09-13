<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_id',
    'recipient_name',
    'contact_number',
    'line1',
    'barangay',
    'city',
    'province',
    'postal_code',
    'latitude',
    'longitude',
])]
class Address extends Model
{
    // Appended so every place that serializes an Address (client's order
    // list, admin views, packing slip) can show a "no address yet" reminder
    // without duplicating this completeness check on the frontend.
    protected $appends = ['is_complete'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isComplete(): bool
    {
        return filled($this->recipient_name)
            && filled($this->contact_number)
            && filled($this->line1)
            && filled($this->city)
            && filled($this->province)
            && filled($this->postal_code);
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->isComplete();
    }
}
