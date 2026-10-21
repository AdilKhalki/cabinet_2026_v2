<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

/**
 * Une requête Livewire (/livewire/update) rejouée par un utilisateur non autorisé,
 * avec un snapshot valide capturé sur une page admin, doit être refusée.
 */
class LivewireActionAuthorizationTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    public function test_secretary_cannot_replay_admin_component_action(): void
    {
        config(['activitylog.enabled' => false]);
        $admin = $this->admin();
        $victim = $this->doctor();

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();
        $snapshot = $this->extractSnapshot($html, 'admin.user-index');

        $this->actingAs($this->secretary())
            ->withHeaders(['X-Livewire' => 'true'])
            ->postJson(app(HandleRequests::class)->getUpdateUri(), [
                '_token' => csrf_token(),
                'components' => [[
                    'snapshot' => $snapshot,
                    'updates' => new \stdClass,
                    'calls' => [['path' => '', 'method' => 'delete', 'params' => [$victim->id]]],
                ]],
            ])
            ->assertForbidden();

        $this->assertNotNull(User::find($victim->id));
    }

    /** Contrôle positif : la même requête faite par l'admin aboutit (le payload est valide). */
    public function test_admin_replay_of_same_payload_succeeds(): void
    {
        config(['activitylog.enabled' => false]);
        $admin = $this->admin();
        $victim = $this->secretary();

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();
        $snapshot = $this->extractSnapshot($html, 'admin.user-index');

        $this->withHeaders(['X-Livewire' => 'true'])
            ->postJson(app(HandleRequests::class)->getUpdateUri(), [
                '_token' => csrf_token(),
                'components' => [[
                    'snapshot' => $snapshot,
                    'updates' => new \stdClass,
                    'calls' => [['path' => '', 'method' => 'delete', 'params' => [$victim->id]]],
                ]],
            ])
            ->assertOk();

        $this->assertNull(User::find($victim->id));
    }

    private function extractSnapshot(string $html, string $componentName): string
    {
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);

        foreach ($matches[1] as $encoded) {
            $json = html_entity_decode($encoded, ENT_QUOTES);
            if ((json_decode($json, true)['memo']['name'] ?? null) === $componentName) {
                return $json;
            }
        }

        $this->fail("Snapshot du composant [{$componentName}] introuvable.");
    }
}
