<?php

namespace Tests\Feature\Admin\PermissionController;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePermissionTest extends TestCase
{
    private User $user;
    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->permission = Permission::factory()->create(['name' => 'test_permission']);
    }

    #[Test]
    public function update_permission_successfully()
    {
        $updateData = [
            'nombre' => 'updated.permission',
        ];

        $response = $this->putJson(route('permission.update', ['permission' => $this->permission->id]), $updateData);

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $this->permission->id,
            'nombre' => 'updated.permission',
        ]);

        $this->assertDatabaseHas('permissions', [
            'id' => $this->permission->id,
            'name' => 'updated.permission',
        ]);
    }

    public static function validationErrorsProvider(): array
    {
        $base = [
            'nombre' => 'Operator',
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
    #[DataProvider('validationErrorsProvider')]
    public function status_422_to_update_permission(array $data, string $error_field, string $error_message)
    {
        $permission = Permission::factory()->create([
            'name' => 'test.permission'
        ]);

        $response = $this->putJson(route('permission.update', ['permission' => $this->permission->id]), $data);

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
    public function unauthenticated_update_returns_401()
    {
        Auth::logout();

        $updateData = [
            'nombre' => 'updated.permission',
        ];

        $response = $this->putJson("/api/permission/{$this->permission->id}", $updateData);

        $response->assertStatus(401);
    }

    
}
