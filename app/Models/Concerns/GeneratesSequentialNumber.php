<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Numérotation automatique au format PREFIX-ANNEE-NNNNN (ex. ORD-2026-00042).
 *
 * Le modèle doit définir sequentialNumberConfig() : [colonne, préfixe].
 */
trait GeneratesSequentialNumber
{
    /** Vrai si le numéro a été attribué automatiquement (et peut donc être réattribué). */
    protected bool $sequentialNumberGenerated = false;

    /** @return array{0: string, 1: string} [colonne, préfixe] */
    abstract protected function sequentialNumberConfig(): array;

    public static function bootGeneratesSequentialNumber(): void
    {
        static::creating(function (self $model) {
            [$column, $prefix] = $model->sequentialNumberConfig();

            if (empty($model->{$column})) {
                $model->{$column} = static::nextSequentialNumber($column, $prefix);
                $model->sequentialNumberGenerated = true;
            }
        });
    }

    /**
     * Prochain numéro à partir du plus grand numéro existant (et non du nombre de lignes),
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

    /**
     * Si deux enregistrements simultanés obtiennent le même numéro, l'index unique
     * refuse le second : on lui attribue alors le numéro suivant et on réessaie.
     */
    protected function performInsert(Builder $query)
    {
        $attempts = 0;

        while (true) {
            try {
                return parent::performInsert($query);
            } catch (UniqueConstraintViolationException $e) {
                [$column, $prefix] = $this->sequentialNumberConfig();

                // Numéro saisi manuellement, ou trop de tentatives : on ne masque pas l'erreur
                if (! $this->sequentialNumberGenerated || ++$attempts >= 5) {
                    throw $e;
                }

                $this->{$column} = static::nextSequentialNumber($column, $prefix);
            }
        }
    }
}
