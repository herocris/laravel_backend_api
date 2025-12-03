<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\WeaponConfiscationController;

use App\Models\Weapon;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreWeaponConfiscationTest extends TestCase
{
    private User $admin;
    private Confiscation $confiscation;
    private Weapon $weapon;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->confiscation = Confiscation::factory()->create();
        $this->weapon = Weapon::factory()->create();
    }

    #[Test]
    public function store_creates_weaponConfiscation(): void
    {
        $payload = [
            'cantidad' => 10,
            'decomiso' => $this->confiscation->id,
            'arma' => $this->weapon->id,
            'foto' => UploadedFile::fake()->image('weapon_confiscation_photo.png'),
        ];

        $response = $this->postJson(route('weaponConfiscation.store'), $payload);
        $response->assertOk();

        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'cantidad' => $payload['cantidad'],
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'arma' => [
                'identificador' => $this->weapon->id,
                'descripcion' => $this->weapon->description,
            ],
            'foto' => $response->json('foto'),
        ]);

        $this->assertDatabaseHas('weapon_confiscations', [
            'id' => $response->json('identificador'),
        ]);
    }

    public static function invalidWeaponConfiscationDataProvider(): array
    {
        $invalidData = [
            'cantidad' => 10,
            'decomiso' => 0,
            'arma' => 0,
            'foto' => UploadedFile::fake()->image('weapon_confiscation_photo.png'),
        ];
        return [
            'missing cantidad' => [
                array_diff_key($invalidData, ['cantidad' => '']),
                'cantidad',
                'The cantidad field is required.'
            ],
            'missing cantidad format' => [
                array_merge($invalidData, ['cantidad' => 'dgd']),
                'cantidad',
                'The cantidad field must be an integer.'
            ],
            'missing decomiso' => [
                array_diff_key($invalidData, ['decomiso' => '']),
                'decomiso',
                'The confiscation id field is required.'
            ],
            'invalid decomiso' => [
                array_merge($invalidData, ['decomiso' => 861]),
                'decomiso',
                'The selected confiscation id is invalid.'
            ],
            'missing arma' => [
                array_diff_key($invalidData, ['arma' => '']),
                'arma',
                'The weapon id field is required.'
            ],
            'invalid arma' => [
                array_merge($invalidData, ['arma' => 861]),
                'arma',
                'The selected weapon id is invalid.'
            ],

            'missing foto' => [
                array_diff_key($invalidData, ['foto' => '']),
                'foto',
                'The foto field is required.'
            ],
            'invalid foto format' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('weapon_confiscation_photo.pdf')]),
                'foto',
                [
                    "The foto field must be an image.",
                    "The foto field must be a file of type: png."
                ]
            ],
            'invalid foto image format' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('weapon_confiscation_photo.jpg')]),
                'foto',
                [
                    "The foto field must be a file of type: png."
                ]
            ],
            'invalid foto image size' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('weapon_confiscation_photo.png', 3000)]),
                'foto',
                [
                    "The foto field must not be greater than 2048 kilobytes.",
                ]
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidWeaponConfiscationDataProvider')]
    public function status_422_to_store_weapon_confiscation(array $data, string $error_field, string|array $error_message): void
    {
        //se agrega decomiso y municion si no son el campo con error
        //por no se pueden agregar en el data provider porque dependen de IDs creados en setUp
        if ($error_field !== 'decomiso') {
            $data['decomiso'] = $this->confiscation->id;
        }
        if ($error_field !== 'arma') {
            $data['arma'] = $this->weapon->id;
        }

        $response = $this->postJson(route('weaponConfiscation.store'), $data);
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
    public function unauthenticated_store_ammunition_confiscation_returns_401(): void
    {
        Auth::logout();

        $payload = [
            'cantidad' => 10,
            'decomiso' => $this->confiscation->id,
            'arma' => $this->weapon->id,
            'foto' => UploadedFile::fake()->image('weapon_confiscation_photo.png'),
        ];

        $response = $this->postJson(route('weaponConfiscation.store'), $payload);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
