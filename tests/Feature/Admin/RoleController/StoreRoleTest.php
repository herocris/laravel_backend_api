<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RoleController;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreRoleTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        Role::factory()->create(['name' => 'admin']);
    }

    #[Test]
    public function store_creates_role_and_syncs_permissions(): void
    {
        $perm1 = Permission::factory()->create(['name' => 'perm.a']);
        $perm2 = Permission::factory()->create(['name' => 'perm.b']);

        $payload = [
            'nombre' => 'Supervisor',
            'permisos' => [$perm1->id, $perm2->id],
        ];

        $response = $this->postJson(route('role.store'), $payload);
        $response->assertOk();
        $response->assertJson([
            'nombre' => 'Supervisor',
            'permisos' => [$perm1->id, $perm2->id],
        ]);
        $this->assertDatabaseHas('roles', [
            'name' => 'Supervisor',
        ]);
        
        $role = Role::where('name', 'Supervisor')->first();
        //assertEqualsCanonicalizing para comparar arrays sin importar el orden
        //esta asercion verifica que los permisos asociados al rol sean exactamente los esperados
        $this->assertEqualsCanonicalizing(
            [$perm1->id, $perm2->id],
            $role->permissions->pluck('id')->toArray()
        );
    }

    public static function invalidRoleDataProvider(): array
    {
        $invalidData = [
            'nombre' => 'Rol de Prueba'
        ];
        return [
            'missing nombre' => [
                array_diff_key($invalidData, ['nombre' => '']),
                'nombre',
                'The nombre field is required.'
            ],
            'unique nombre' => [
                array_merge($invalidData, ['nombre' => 'admin']),
                'nombre',
                'The nombre has already been taken.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidRoleDataProvider')]
    public function status_422_to_store_role(array $data, string $error_field, string $error_message): void
    {
        $response = $this->postJson(route('role.store'), $data);
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
    public function unauthenticated_store_role_returns_401(): void
    {
        Auth::logout();
        $response = $this->postJson(route('role.store'), ['nombre' => 'NoAuth']);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
