<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\DrugPresentationController;

use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreDrugPresentationTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        DrugPresentation::factory()->create(['description' => 'test_drugPresentation', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function store_creates_drugPresentation(): void
    {
        $payload = [
            'descripcion' => 'droga de prueba',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->postJson(route('drugPresentation.store'), $payload);
        $response->assertOk();

        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'descripcion' => $payload['descripcion'],
            'logo' => $response->json('logo'),
        ]);

        $this->assertDatabaseHas('drug_presentations', [
            'id' => $response->json('identificador'),
        ]);
    }

    public static function invalidDrugPresentationDataProvider(): array
    {
        $invalidData = [
            'descripcion' => 'Droga de Prueba',
            'logo' => UploadedFile::fake()->image('droga_logo.png')
        ];
        return [
            'missing descripcion' => [
                array_diff_key($invalidData, ['descripcion' => '']),
                'descripcion',
                'The descripcion field is required.'
            ],
            'missing logo' => [
                array_diff_key($invalidData, ['logo' => '']),
                'logo',
                'The logo field is required.'
            ],
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
            'unique descripcion' => [
                array_merge($invalidData, ['descripcion' => 'test_drugPresentation']),
                'descripcion',
                'The descripcion has already been taken.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidDrugPresentationDataProvider')]
    public function status_422_to_store_drugPresentation(array $data, string $error_field, string|array $error_message): void
    {
        $response = $this->postJson(route('drugPresentation.store'), $data);
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
    public function unauthenticated_store_drugPresentation_returns_401(): void
    {
        Auth::logout();

        $payload = [
            'descripcion' => 'droga de prueba',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->postJson(route('drugPresentation.store'), $payload);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
