<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\LabTest;
use App\Models\MedicalImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Déplace les fichiers médicaux envoyés avant la sécurisation
 * (documents, imagerie, PDF de laboratoire) du disque public vers le disque privé.
 * Le chemin enregistré en base ne change pas : seul le disque change.
 *
 * Usage : php artisan cabinet:secure-files [--dry-run]
 */
class MoveMedicalFilesToPrivate extends Command
{
    protected $signature = 'cabinet:secure-files {--dry-run : Affiche ce qui serait déplacé sans rien modifier}';

    protected $description = 'Déplace les fichiers médicaux du stockage public vers le stockage privé';

    public function handle(): int
    {
        $sources = [
            ['documents', 'file_path', Document::DISK],
            ['medical_images', 'file_path', MedicalImage::DISK],
            ['lab_tests', 'pdf_path', LabTest::DISK],
        ];

        $public = Storage::disk('public');
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        foreach ($sources as [$table, $column, $privateDisk]) {
            $private = Storage::disk($privateDisk);
            $moved = $missing = $already = 0;

            $paths = DB::table($table)->whereNotNull($column)->distinct()->pluck($column);

            foreach ($paths as $path) {
                if ($private->exists($path)) {
                    $already++;

                    continue;
                }
                if (! $public->exists($path)) {
                    $missing++;

                    continue;
                }

                if (! $dryRun) {
                    $private->writeStream($path, $public->readStream($path));

                    // Ne supprimer l'original qu'après une copie vérifiée
                    if ($private->exists($path) && $private->size($path) === $public->size($path)) {
                        $public->delete($path);
                    }
                }
                $moved++;
            }

            $rows[] = [$table, $moved, $already, $missing];
        }

        $this->table(['Table', $dryRun ? 'À déplacer' : 'Déplacés', 'Déjà privés', 'Introuvables'], $rows);

        if ($dryRun) {
            $this->info('Simulation uniquement : relancez sans --dry-run pour déplacer les fichiers.');
        }

        return self::SUCCESS;
    }
}
