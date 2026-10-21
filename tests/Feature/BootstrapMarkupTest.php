<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

/**
 * L'application utilise Bootstrap 4 : la classe Bootstrap 3 « fade in » rend
 * l'élément invisible (opacity: 0). Pour une fenêtre modale, cela crée un voile
 * transparent qui bloque tous les clics : la page semble plantée.
 */
class BootstrapMarkupTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    public function test_no_view_uses_the_bootstrap_3_fade_in_class(): void
    {
        $offenders = [];

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            foreach (file($file->getRealPath()) as $n => $line) {
                if (preg_match('/class="[^"]*\bfade in\b/', $line)) {
                    $offenders[] = $file->getRelativePathname().':'.$n + 1;
                }
            }
        }

        $this->assertSame([], $offenders, 'Remplacer « fade in » par « fade show » (Bootstrap 4).');
    }

    public function test_new_user_modal_is_visible(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UserIndex::class)
            ->call('openCreate')
            ->assertSet('showModal', true)
            ->assertSeeHtml('class="modal fade show"')
            ->assertSee('Nouvel utilisateur');
    }
}
