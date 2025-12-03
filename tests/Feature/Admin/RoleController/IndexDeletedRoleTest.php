<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RoleController;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role as ModelsRole;
use Tests\TestCase;

class IndexDeletedRoleTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $roles = Role::factory()->count(10)->create();
        $roles->each->delete(); //para borrar los usuarios creados
    }

    #[Test]
    public function indexDeleted_lists_only_soft_deleted_roles(): void
    {
        

        $response = $this->getJson(route('role.indexDeleted'));
        $response->assertOk();
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'identificador',
                    'nombre',
                    'permisos',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links' => [
                '*' => [
                    'url',
                    'label',
                    'active',
                ],
            ],
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function indexDeleted_returns_empty_when_no_deleted_roles(): void
    {
        // Restore all deleted roles
        Role::onlyTrashed()->get()->each->restore();
        
        $response = $this->getJson(route('role.indexDeleted'));
        $response->assertOk();
        $response->assertExactJson([            
            'data' => [],
        ]);
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_indexDeleted_returns_401(): void
    {
        Auth::logout();
        $response = $this->getJson(route('role.indexDeleted'));
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
