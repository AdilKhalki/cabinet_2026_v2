<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

/**
 * Matrice d'accès : chaque route GET nommée (sans paramètre) de l'application
 * doit répondre 200 ou 403 selon les permissions de RolesAndPermissionsSeeder.
 */
class RouteAccessMatrixTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    private const ALL = ['admin', 'medecin', 'secretaire'];

    private const CLINICAL = ['admin', 'medecin'];

    private const ADMIN = ['admin'];

    /** route => rôles autorisés (les autres doivent recevoir 403) */
    public static function matrix(): array
    {
        return [
            'home' => self::ALL,
            'patients.index' => self::ALL,
            'patients.create' => self::ALL,
            'appointments.index' => self::ALL,
            'appointments.calendar' => self::ALL,
            'appointments.waiting-room' => self::ALL,
            'appointments.create' => self::ALL,
            'consultations.index' => self::CLINICAL,
            'consultations.create' => self::CLINICAL,
            'prescriptions.index' => self::CLINICAL,
            'prescriptions.create' => self::CLINICAL,
            'lab-tests.index' => self::CLINICAL,
            'lab-tests.create' => self::CLINICAL,
            'medical-images.index' => self::CLINICAL,
            'medical-images.create' => self::CLINICAL,
            'documents.index' => self::ALL,
            'documents.create' => self::ALL,
            'medical-reports.index' => self::CLINICAL,
            'medical-reports.create' => self::CLINICAL,
            'payments.index' => self::ALL,
            'reports.financial' => self::CLINICAL,
            'reports.stats' => self::CLINICAL,
            'notifications.index' => self::ALL,
            'medical-acts.index' => self::ADMIN,
            'medications.index' => self::ADMIN,
            'admin.users.index' => self::ADMIN,
            'admin.roles.index' => self::ADMIN,
            'admin.activity-log' => self::ADMIN,
            'admin.settings' => self::ADMIN,
            'api.appointments.calendar' => self::ALL,
            'api.medications.search' => self::CLINICAL,
            'profile.edit' => self::ALL,
            'appearance.edit' => self::ALL,
        ];
    }

    public static function routeRoleProvider(): array
    {
        $cases = [];
        foreach (self::matrix() as $route => $allowed) {
            foreach (self::ALL as $role) {
                $cases["{$role} -> {$route}"] = [$role, $route, in_array($role, $allowed, true)];
            }
        }

        return $cases;
    }

    #[DataProvider('routeRoleProvider')]
    public function test_role_access_matrix(string $role, string $routeName, bool $allowed): void
    {
        config(['activitylog.enabled' => false]);
        $this->actingAs($this->userWithRole($role));

        $response = $this->get(route($routeName));

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }

    public static function guestRouteProvider(): array
    {
        return array_combine(
            array_keys(self::matrix()),
            array_map(fn ($r) => [$r], array_keys(self::matrix())),
        );
    }

    #[DataProvider('guestRouteProvider')]
    public function test_guest_is_redirected_to_login(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_on_parameterized_routes(): void
    {
        foreach ($this->appGetRoutes(withParameters: true) as $route) {
            $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $this->get('/'.ltrim($uri, '/'))->assertRedirect(route('login'));
        }
    }

    public function test_user_without_role_cannot_reach_business_modules(): void
    {
        $this->seedRoles();
        $this->actingAs(User::factory()->create());

        foreach (self::matrix() as $route => $allowed) {
            if (in_array($route, ['home', 'notifications.index', 'profile.edit', 'appearance.edit'], true)) {
                continue;
            }
            $this->get(route($route))->assertForbidden();
        }
    }

    /** Toute nouvelle route GET de l'application doit être ajoutée à la matrice. */
    public function test_matrix_covers_every_application_get_route(): void
    {
        $missing = collect($this->appGetRoutes(withParameters: false))
            ->map(fn (Route $r) => $r->getName())
            ->reject(fn ($name) => $name === null || $name === 'dashboard' || $name === 'security.edit')
            ->diff(array_keys(self::matrix()))
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Routes absentes de la matrice d\'accès : '.implode(', ', $missing));
    }

    /** @return array<int, Route> */
    private function appGetRoutes(bool $withParameters): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => in_array('GET', $r->methods(), true))
            ->filter(fn (Route $r) => in_array('auth', $r->gatherMiddleware(), true))
            ->filter(fn (Route $r) => str_starts_with((string) $r->getControllerClass(), 'App\\')
                || in_array($r->getName(), ['profile.edit', 'appearance.edit', 'security.edit'], true))
            ->filter(fn (Route $r) => $withParameters === str_contains($r->uri(), '{'))
            ->values()
            ->all();
    }
}
