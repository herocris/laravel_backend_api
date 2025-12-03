<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\AmmunitionConfiscationController;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreAmmunitionConfiscationTest extends TestCase
{
    private User $admin;
    private Confiscation $confiscation;
    private Ammunition $ammunition;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->confiscation = Confiscation::factory()->create();
        $this->ammunition = Ammunition::factory()->create();
    }

    #[Test]
    public function store_creates_ammunitionConfiscation(): void
    {
        $payload = [
            'cantidad' => 10,
            'decomiso' => $this->confiscation->id,
            'municion' => $this->ammunition->id,
            'foto' => UploadedFile::fake()->image('ammunition_confiscation_photo.png'),
        ];

        $response = $this->postJson(route('ammunitionConfiscation.store'), $payload);
        $response->assertOk();

        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'cantidad' => $payload['cantidad'],
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'municion' => [
                'identificador' => $this->ammunition->id,
                'descripcion' => $this->ammunition->description,
            ],
            'foto' => $response->json('foto'),
        ]);

        $this->assertDatabaseHas('ammunition_confiscations', [
            'id' => $response->json('identificador'),
        ]);
    }

    public static function invalidAmmunitionConfiscationDataProvider(): array
    {
        $invalidData = [
            'cantidad' => 10,
            'decomiso' => 0,
            'municion' => 0,
            'foto' => UploadedFile::fake()->image('ammunition_confiscation_photo.png'),
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
            'missing municion' => [
                array_diff_key($invalidData, ['municion' => '']),
                'municion',
                'The ammunition id field is required.'
            ],
            'invalid municion' => [
                array_merge($invalidData, ['municion' => 861]),
                'municion',
                'The selected ammunition id is invalid.'
            ],

            'missing foto' => [
                array_diff_key($invalidData, ['foto' => '']),
                'foto',
                'The foto field is required.'
            ],
            'invalid foto format' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('ammunition_confiscation_photo.pdf')]),
                'foto',
                [
                    "The foto field must be an image.",
                    "The foto field must be a file of type: png."
                ]
            ],
            'invalid foto image format' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('ammunition_confiscation_photo.jpg')]),
                'foto',
                [
                    "The foto field must be a file of type: png."
                ]
            ],
            'invalid foto image size' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('ammunition_confiscation_photo.png', 3000)]),
                'foto',
                [
                    "The foto field must not be greater than 2048 kilobytes.",
                ]
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidAmmunitionConfiscationDataProvider')]
    public function status_422_to_store_ammunition_confiscation(array $data, string $error_field, string|array $error_message): void
    {
        //se agrega decomiso y municion si no son el campo con error
        //por no se pueden agregar en el data provider porque dependen de IDs creados en setUp
        if ($error_field !== 'decomiso') {
            $data['decomiso'] = $this->confiscation->id;
        }
        if ($error_field !== 'municion') {
            $data['municion'] = $this->ammunition->id;
        }

        $response = $this->postJson(route('ammunitionConfiscation.store'), $data);
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
            'municion' => $this->ammunition->id,
            'foto' => UploadedFile::fake()->image('ammunition_confiscation_photo.png'),
        ];

        $response = $this->postJson(route('ammunitionConfiscation.store'), $payload);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
