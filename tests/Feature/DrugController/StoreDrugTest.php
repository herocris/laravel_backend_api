<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\DrugController;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreDrugTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        Drug::factory()->create(['description' => 'test_drug', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function store_creates_drug(): void
    {
        $payload = [
            'descripcion' => 'droga de prueba',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->postJson(route('drug.store'), $payload);
        $response->assertOk();

        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'descripcion' => $payload['descripcion'],
            'logo' => $response->json('logo'),
        ]);

        $this->assertDatabaseHas('drugs', [
            'id' => $response->json('identificador'),
        ]);
    }

    public static function invalidDrugDataProvider(): array
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
                array_merge($invalidData, ['descripcion' => 'test_drug']),
                'descripcion',
                'The descripcion has already been taken.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidDrugDataProvider')]
    public function status_422_to_store_drug(array $data, string $error_field, string|array $error_message): void
    {
        $response = $this->postJson(route('drug.store'), $data);
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
    public function unauthenticated_store_drug_returns_401(): void
    {
        Auth::logout();

        $payload = [
            'descripcion' => 'droga de prueba',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->postJson(route('drug.store'), $payload);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
