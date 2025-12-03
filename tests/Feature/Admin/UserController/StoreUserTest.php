<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\UserController;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreUserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);        
    }
    #[Test]
    public function store_creates_user_successfully(): void
    {
        $role = Role::factory()->create(['name' => 'Role1']);
        $role2 = Role::factory()->create(['name' => 'Role2']);
        $user=[
            'nombre' => 'User1',
            'correo' => 'user1@example.com',
            'password' => 'password123',
            'roles' => [$role->id, $role2->id],
            'permisos' => [],
        ];

        $response = $this->postJson(route('user.store'), $user);

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'roles' => [$role->id, $role2->id],
            'permisos' => [],
        ]);
        // Verifica persistencia con nombres de columnas reales
        $this->assertDatabaseHas('users', [
            'name' => $user['nombre'],
            'email' => $user['correo'],
        ]);
    }

    #[Test]
    public function store_hashes_password(): void
    {
        $payload = [
            'nombre' => 'HashUser',
            'correo' => 'hashuser@example.com',
            'password' => 'plainSecret',
        ];
        $this->postJson(route('user.store'), $payload)->assertOk();

        $created = User::where('email', $payload['correo'])->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('plainSecret', $created->password));
        $this->assertNotEquals('plainSecret', $created->password);
    }

    #[Test]
    public function store_response_hides_sensitive_fields(): void
    {
        $payload = [
            'nombre' => 'HiddenUser',
            'correo' => 'hiddenuser@example.com',
            'password' => 'secret123',
        ];
        $response = $this->postJson(route('user.store'), $payload)->assertOk();

        $data = $response->getData(true);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }


    public static function invalidUserDataProvider(): array
    {
        $invalidData = [
            'nombre' => 'Usuario de Prueba',
            'correo' => 'usuario@prueba.com',
            'password' => 'password123',
        ];
        return [
            'missing name' => [
                array_diff_key($invalidData, ['nombre' => '']),
                'nombre',
                'The nombre field is required.'
            ],
            'missing email' => [
                array_diff_key($invalidData, ['correo' => '']),//array_diff_key compara las claves de los arrays y elimina las que coinciden
                'correo',
                'The correo field is required.'
            ],
            'unique email' => [
                array_merge($invalidData, ['correo' => 'admin@yahoo.es']),
                'correo',
                'The correo has already been taken.'
            ],
            'invalid email' => [
                array_merge($invalidData, ['correo' => 'invalid-email']),//array_merge sobre escribe el valor de una clave
                'correo',
                'The correo field must be a valid correo address.'
            ],
            'missing password' => [
                array_diff_key($invalidData, ['password' => '']),
                'password',
                'The password field is required.'
            ],
            'invalid length password' => [
                array_merge($invalidData, ['password' => 'short']),
                'password',
                'The password field must be at least 6 characters.'
            ],
        ];
    }
    #[Test]
    #[DataProvider('invalidUserDataProvider')]
    public function status_422_to_store_user(array $data, string $error_field, string $error_message): void
    {
        $response = $this->postJson(route('user.store'), $data);
        $response->assertUnprocessable();
        $response->assertExactJson([
            "error" => [
                $error_field => [
                    $error_message
                ]
            ],
            "code" => 422
        ]);
    }

        #[Test]
    public function unauthenticated_store_user_returns_401(): void
    {
        Auth::logout();
        $user = [
            'nombre' => 'NoAuth',
            'correo' => 'noauth@example.com',
            'password' => 'password123',
        ];
        $response = $this->postJson(route('user.store'), $user);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }

}
