<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTestType extends Model
{
    protected $fillable = [
        'name', 'category', 'unit', 'normal_min', 'normal_max', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
