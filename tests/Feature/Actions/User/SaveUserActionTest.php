<?php

namespace Tests\Feature\Actions\User;

use App\Actions\User\SaveUserAction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Tests\TestCase;

class SaveUserActionTest extends TestCase
{
    public function test_creates_user_and_assignments_without_an_http_session(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $attributes = User::factory()->raw(['password' => 'local-password']);

        $user = app(SaveUserAction::class)->execute(new User, [
            ...$attributes,
            'roles' => [(string) $role->id],
            'permissions' => [(string) $permission->id],
        ]);

        $this->assertModelExists($user);
        $this->assertSame($attributes['email'], $user->email);
        $this->assertTrue(Hash::check('local-password', $user->password));
        $this->assertSame([$role->id], $user->roles()->pluck('roles.id')->all());
        $this->assertSame([$permission->id], $user->permissions()->pluck('permissions.id')->all());
        $this->assertNull(auth('api')->user());
    }

    public function test_rolls_back_profile_and_roles_if_permission_assignment_fails(): void
    {
        $user = User::factory()->create();
        $originalName = $user->name;
        $originalRole = Role::factory()->create();
        $replacementRole = Role::factory()->create();
        $user->assignRole($originalRole);

        try {
            app(SaveUserAction::class)->execute($user, [
                'name' => 'Changed profile',
                'roles' => [$replacementRole->id],
                'permissions' => [999999],
            ]);
            $this->fail('Expected permission assignment to fail.');
        } catch (PermissionDoesNotExist) {
            $this->assertSame($originalName, $user->fresh()->name);
            $this->assertSame([$originalRole->id], $user->fresh()->roles()->pluck('roles.id')->all());
            $this->assertDatabaseCount('model_has_permissions', 0);
        }
    }
}
