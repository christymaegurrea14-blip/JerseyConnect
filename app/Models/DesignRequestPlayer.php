<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'design_request_id',
    'name',
    'number',
    'position',
    'size',
])]
class DesignRequestPlayer extends Model
{
    public function designRequest(): BelongsTo
    {
        return $this->belongsTo(DesignRequest::class);
    }
}
