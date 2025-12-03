<?php

namespace Tests\Feature\Admin\AmmunitionController;

use App\Models\Ammunition;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateAmmunitionTest extends TestCase
{
    private User $user;
    private Ammunition $ammunition;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->ammunition = Ammunition::factory()->create(['description' => 'test_ammunition']);
        Ammunition::factory()->create(['description' => 'unique_ammunition', 'logo' => 'test_logo.png']);
    }

    #[Test]
    public function update_ammunition_successfully()
    {
        $updateData = [
            'descripcion' => 'updated.ammunition',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->putJson(route('ammunition.update', ['ammunition' => $this->ammunition->id]), $updateData);

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'descripcion' => $updateData['descripcion'],
            'logo' => $response->json('logo'),
        ]);

        $this->assertDatabaseHas('ammunitions', [
            'id' => $this->ammunition->id,
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
    public function status_422_to_update_ammunition(array $data, string $error_field, string|array $error_message): void
    {
        $response = $this->putJson(route('ammunition.update', ['ammunition' => $this->ammunition->id]), $data);
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
            'descripcion' => 'updated.ammunition',
            'logo' => UploadedFile::fake()->image('droga_logo.png'),
        ];

        $response = $this->putJson(route("ammunition.update", ['ammunition' => $this->ammunition->id]), $updateData);

        $response->assertStatus(401);
    }

    
}
