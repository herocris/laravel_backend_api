<?php

namespace Tests\Feature\Actions\Role;

use App\Actions\Role\SaveRoleAction;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Tests\TestCase;

class SaveRoleActionTest extends TestCase
{
    public function test_creates_api_role_with_permissions_without_an_http_session(): void
    {
        $permission = Permission::factory()->create();

        $role = app(SaveRoleAction::class)->execute(new Role, [
            'name' => 'Local operator',
            'guard_name' => 'web',
            'permissions' => [(string) $permission->id],
        ]);

        $this->assertModelExists($role);
        $this->assertSame('Local operator', $role->name);
        $this->assertSame('api', $role->guard_name);
        $this->assertSame([$permission->id], $role->permissions()->pluck('permissions.id')->all());
        $this->assertNull(auth('api')->user());
    }

    public function test_rolls_back_role_changes_if_permission_assignment_fails(): void
    {
        $role = Role::factory()->create();
        $originalName = $role->name;
        $permission = Permission::factory()->create();
        $role->givePermissionTo($permission);

        try {
            app(SaveRoleAction::class)->execute($role, [
                'name' => 'Changed role',
                'permissions' => [999999],
            ]);
            $this->fail('Expected permission assignment to fail.');
        } catch (PermissionDoesNotExist) {
            $this->assertSame($originalName, $role->fresh()->name);
            $this->assertSame([$permission->id], $role->fresh()->permissions()->pluck('permissions.id')->all());
        }
    }
}
