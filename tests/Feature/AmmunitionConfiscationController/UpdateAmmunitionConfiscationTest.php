<?php

namespace Tests\Feature\Admin\AmmunitionConfiscationController;

use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateAmmunitionConfiscationTest extends TestCase
{
    private User $user;
    private Confiscation $confiscation;
    private Ammunition $ammunition;
    private AmmunitionConfiscation $ammunitionConfiscation;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->confiscation = Confiscation::factory()->create();
        $this->ammunition = Ammunition::factory()->create();
        $this->ammunitionConfiscation = AmmunitionConfiscation::factory()->create();
        
    }

    #[Test]
    public function update_ammunitionConfiscation_successfully()
    {
        $updateData = [
            'cantidad' => 6,
            'decomiso' => $this->confiscation->id,
            'municion' => $this->ammunition->id,
            'foto' => UploadedFile::fake()->image('ammunition_confiscation_photo_updated.png'),
        ];

        $response = $this->putJson(route('ammunitionConfiscation.update', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]), $updateData);

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'cantidad' => $updateData['cantidad'],
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
            'id' => $this->ammunitionConfiscation->id,
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
    public function status_422_to_update_ammunition_confiscation(array $data, string $error_field, string|array $error_message): void
    {
        //se agrega decomiso y municion si no son el campo con error
        //por no se pueden agregar en el data provider porque dependen de IDs creados en setUp
        if ($error_field !== 'decomiso') {
            $data['decomiso'] = $this->confiscation->id;
        }
        if ($error_field !== 'municion') {
            $data['municion'] = $this->ammunition->id;
        }

        $response = $this->putJson(route('ammunitionConfiscation.update', ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]), $data);
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
        $invalidData = [
            'cantidad' => 10,
            'decomiso' => $this->confiscation->id,
            'municion' => $this->ammunition->id,
            'foto' => UploadedFile::fake()->image('ammunition_confiscation_photo.png'),
        ];

        $response = $this->putJson(route("ammunitionConfiscation.update", ['ammunitionConfiscation' => $this->ammunitionConfiscation->id]), $invalidData);

        $response->assertStatus(401);
    }

    
}
