<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\DrugConfiscationController;

use App\Models\Ammunition;
use App\Models\DrugConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreDrugConfiscationTest extends TestCase
{
    private User $admin;
    private Confiscation $confiscation;
    private Drug $drug;
    private DrugPresentation $drugPresentation;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        $this->confiscation = Confiscation::factory()->create();
        $this->drug = Drug::factory()->create();
        $this->drugPresentation = DrugPresentation::factory()->create();
    }

    #[Test]
    public function store_creates_drugConfiscation(): void
    {
        $payload = [
            'cantidad' => 10,
            'peso' => 5.5,
            'decomiso' => $this->confiscation->id,
            'droga' => $this->drug->id,
            'presentacion' => $this->drugPresentation->id,
            'foto' => UploadedFile::fake()->image('drug_confiscation_photo.png'),
        ];

        $response = $this->postJson(route('drugConfiscation.store'), $payload);
        $response->assertOk();

        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'peso' => $payload['peso'],
            'cantidad' => $payload['cantidad'],
            'decomiso' => [
                'identificador' => $this->confiscation->id,
                'observacion' => $this->confiscation->observation,
            ],
            'droga' => [
                'identificador' => $this->drug->id,
                'descripcion' => $this->drug->description,
            ],
            'presentacion' => [
                'identificador' => $this->drugPresentation->id,
                'descripcion' => $this->drugPresentation->description,
            ],
            'foto' => $response->json('foto'),
        ]);

        $this->assertDatabaseHas('drug_confiscations', [
            'id' => $response->json('identificador'),
        ]);
    }

    public static function invalidDrugConfiscationDataProvider(): array
    {
        $invalidData = [
            'peso' => 5.5,
            'cantidad' => 10,
            'decomiso' => 0,
            'droga' => 0,
            'presentacion' => 0,
            'foto' => UploadedFile::fake()->image('drug_confiscation_photo.png'),
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
            'missing droga' => [
                array_diff_key($invalidData, ['droga' => '']),
                'droga',
                'The drug id field is required.'
            ],
            'invalid droga' => [
                array_merge($invalidData, ['droga' => 861]),
                'droga',
                'The selected drug id is invalid.'
            ],
            'missing presentacion' => [
                array_diff_key($invalidData, ['presentacion' => '']),
                'presentacion',
                'The drug presentation id field is required.'
            ],
            'invalid presentacion' => [
                array_merge($invalidData, ['presentacion' => 861]),
                'presentacion',
                'The selected drug presentation id is invalid.'
            ],

            'missing foto' => [
                array_diff_key($invalidData, ['foto' => '']),
                'foto',
                'The foto field is required.'
            ],
            'invalid foto format' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('drug_confiscation_photo.pdf')]),
                'foto',
                [
                    "The foto field must be an image.",
                    "The foto field must be a file of type: png."
                ]
            ],
            'invalid foto image format' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('drug_confiscation_photo.jpg')]),
                'foto',
                [
                    "The foto field must be a file of type: png."
                ]
            ],
            'invalid foto image size' => [
                array_merge($invalidData, ['foto' => UploadedFile::fake()->create('drug_confiscation_photo.png', 3000)]),
                'foto',
                [
                    "The foto field must not be greater than 2048 kilobytes.",
                ]
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidDrugConfiscationDataProvider')]
    public function status_422_to_store_drug_confiscation(array $data, string $error_field, string|array $error_message): void
    {
        //se agrega decomiso y municion si no son el campo con error
        //por no se pueden agregar en el data provider porque dependen de IDs creados en setUp
        if ($error_field !== 'decomiso') {
            $data['decomiso'] = $this->confiscation->id;
        }
        if ($error_field !== 'droga') {
            $data['droga'] = $this->drug->id;
        }
        if ($error_field !== 'presentacion') {
            $data['presentacion'] = $this->drugPresentation->id;
        }

        $response = $this->postJson(route('drugConfiscation.store'), $data);

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
            'peso' => 5.5,
            'decomiso' => $this->confiscation->id,
            'droga' => $this->drug->id,
            'presentacion' => $this->drugPresentation->id,
            'foto' => UploadedFile::fake()->image('drug_confiscation_photo.png'),
        ];

        $response = $this->postJson(route('drugConfiscation.store'), $payload);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
