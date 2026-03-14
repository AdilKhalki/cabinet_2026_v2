<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalAct extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'category',
        'price', 'duration_minutes', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
