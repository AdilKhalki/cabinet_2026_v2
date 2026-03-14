<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    protected $fillable = [
        'name', 'generic_name', 'dosage_form', 'strength',
        'manufacturer', 'description', 'contraindications', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function prescriptionLines(): HasMany
    {
        return $this->hasMany(PrescriptionLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('generic_name', 'like', "%{$search}%");
        });
    }
}
