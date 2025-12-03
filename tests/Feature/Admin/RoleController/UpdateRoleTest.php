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

class UpdateRoleTest extends TestCase
{
    private User $admin;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->role = Role::factory()->create(['name' => 'Operator']);
    }

    #[Test]
    public function update_role_successfully_and_syncs_permissions(): void
    {
        $perm1 = Permission::factory()->create(['name' => 'perm.x']);
        $perm2 = Permission::factory()->create(['name' => 'perm.y']);

        $payload = [
            'nombre' => 'Administrator',
            'permisos' => [$perm1->id, $perm2->id],
        ];

        $response = $this->putJson(route('role.update', ['role' => $this->role->id]), $payload);
        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->role->id,
            'nombre' => 'Administrator',
            'permisos' => [$perm1->id, $perm2->id],
        ]);
        $this->assertDatabaseHas('roles', [
            'name' => 'Administrator',
        ]);

        $this->role->refresh();
        //assertEqualsCanonicalizing para comparar arrays sin importar el orden
        //esta asercion verifica que los permisos asociados al rol sean exactamente los esperados
        $this->assertEqualsCanonicalizing(
            [$perm1->id, $perm2->id],
            $this->role->permissions->pluck('id')->toArray()
        );
    }

    public static function invalidUpdateRoleDataProvider(): array
    {
        $base = [
            'nombre' => 'Operator',
            'permisos' => [],
        ];
        return [
            'missing nombre' => [
                array_diff_key($base, ['nombre' => '']),
                'nombre',
                'The nombre field is required.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidUpdateRoleDataProvider')]
    public function status_422_to_update_role(array $data, string $error_field, string $error_message): void
    {
        $response = $this->putJson(route('role.update', ['role' => $this->role->id]), $data);
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
    public function unauthenticated_update_role_returns_401(): void
    {
        Auth::logout();
        $response = $this->putJson(route('role.update', ['role' => $this->role->id]), ['nombre' => 'Admin']);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
