<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consulta extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'total_cedulas',
        'cedulas',
        'processed',
        'status',
        'error_message',
    ];

    protected $casts = [
        'cedulas' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ConsultaResult::class);
    }
}
