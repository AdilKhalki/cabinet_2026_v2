<?php

namespace App\Models\Concerns;

trait GeneratesSequentialNumber
{
    /**
     * Génère le prochain numéro au format PREFIX-ANNEE-NNNNN
     * à partir du plus grand numéro existant (et non du nombre de lignes),
     * pour éviter les doublons quand la séquence contient des trous.
     */
    public static function nextSequentialNumber(string $column, string $prefix, ?string $year = null): string
    {
        $year ??= date('Y');
        $base = "{$prefix}-{$year}-";

        $last = static::withTrashed()
            ->where($column, 'like', $base.'%')
            ->orderByRaw("LENGTH({$column}) DESC")
            ->orderByDesc($column)
            ->value($column);

        $next = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        return sprintf('%s%05d', $base, $next);
    }
}
