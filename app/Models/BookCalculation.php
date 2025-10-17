<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCalculation extends Model
{
    protected $fillable = [
        'user_id',
        'grid_size',
        'grid_data',
        'visible_stacks',
        'visibility_details',
    ];

    protected $casts = [
        'grid_data' => 'array',
        'visibility_details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}