<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\PermissionController;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StorePermissionTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        Permission::factory()->create(['name' => 'perimission_test']);
    }

    #[Test]
    public function store_creates_permission(): void
    {
        $payload = [
            'nombre' => 'Supervisor',
        ];

        $response = $this->postJson(route('permission.store'), $payload);
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'nombre' => 'Supervisor',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'Supervisor',
        ]);
    }

    public static function invalidPermissionDataProvider(): array
    {
        $invalidData = [
            'nombre' => 'Permiso de Prueba'
        ];
        return [
            'missing nombre' => [
                array_diff_key($invalidData, ['nombre' => '']),
                'nombre',
                'The nombre field is required.'
            ],
            'unique nombre' => [
                array_merge($invalidData, ['nombre' => 'perimission_test']),
                'nombre',
                'The nombre has already been taken.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidPermissionDataProvider')]
    public function status_422_to_store_permission(array $data, string $error_field, string $error_message): void
    {
        $response = $this->postJson(route('permission.store'), $data);
        $response->assertUnprocessable();
        $response->assertExactJson([
            'error' => [
                $error_field => [
                    $error_message
                ]
            ],
            'code' => 422
        ]);
    }

    #[Test]
    public function unauthenticated_store_permission_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('permission.store'), ['nombre' => 'NoAuth']);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
