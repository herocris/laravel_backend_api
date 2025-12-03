<?php

namespace Tests\Feature\Admin\WeaponController;

use App\Models\Weapon;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateWeaponTest extends TestCase
{
    private User $user;
    private Weapon $weapon;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->weapon = Weapon::factory()->create(['description' => 'test_weapon']);
        Weapon::factory()->create(['description' => 'unique_weapon', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function update_weapon_successfully()
    {
        $updateData = [
            'descripcion' => 'updated.weapon',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->putJson(route('weapon.update', ['weapon' => $this->weapon->id]), $updateData);

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'descripcion' => $updateData['descripcion'],
            'logo' => $response->json('logo'),
        ]);

        $this->assertDatabaseHas('weapons', [
            'id' => $this->weapon->id,
        ]);
    }

    public static function validationErrorsProvider(): array
    {
        $invalidData = [
            'descripcion' => 'Droga de Prueba',
            'logo' => UploadedFile::fake()->image('droga_logo.png')
        ];
        return [
            'invalid logo format' => [
                array_merge($invalidData, ['logo' => UploadedFile::fake()->create('droga_logo.pdf')]),
                'logo',
                [
                    "The logo field must be an image.",
                    "The logo field must be a file of type: png."
                ]
            ],
            'invalid logo image format' => [
                array_merge($invalidData, ['logo' => UploadedFile::fake()->create('droga_logo.jpg')]),
                'logo',
                [
                    "The logo field must be a file of type: png."
                ]
            ],
            'invalid logo image size' => [
                array_merge($invalidData, ['logo' => UploadedFile::fake()->create('droga_logo.png', 3000)]),
                'logo',
                [
                    "The logo field must not be greater than 2048 kilobytes.",
                ]
            ],
        ];
    }
    #[Test]
    #[DataProvider('validationErrorsProvider')]
    public function status_422_to_update_weapon(array $data, string $error_field, string|array $error_message): void
    {
        $response = $this->putJson(route('weapon.update', ['weapon' => $this->weapon->id]), $data);
        $response->assertUnprocessable();
        $response->assertExactJson([
            'error' => [
                $error_field => is_array($error_message) ? $error_message : [
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
            'descripcion' => 'updated.weapon',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->putJson(route("weapon.update", ['weapon' => $this->weapon->id]), $updateData);

        $response->assertStatus(401);
    }

    
}
