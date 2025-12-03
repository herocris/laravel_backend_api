<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\ConfiscationController;

use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreConfiscationTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->superAdminLogin();
        Auth::login($this->admin);
        Confiscation::factory()->create();
    }

    #[Test]
    public function store_creates_confiscation(): void
    {
        $payload = [
            'fecha' => '2024-06-01',
            'observacion' => 'observacion de prueba',
            'direccion' => 'direccion de prueba',
            'departamento' => 'departamento de prueba',
            'municipalidad' => 'municipalidad de prueba',
            'latitud' => '12.345678',
            'longitud' => '98.765432',
        ];

        $response = $this->postJson(route('confiscation.store'), $payload);
        $response->assertOk();

        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'fecha' => $payload['fecha'],
            'observacion' => $payload['observacion'],
            'direccion' => $payload['direccion'],
            'departamento' => $payload['departamento'],
            'municipalidad' => $payload['municipalidad'],
            'latitud' => $payload['latitud'],
            'longitud' => $payload['longitud'],
        ]);

        $this->assertDatabaseHas('confiscations', [
            'id' => $response->json('identificador'),
        ]);
    }

    public static function invalidConfiscationDataProvider(): array
    {
        $invalidData = [
            'fecha' => '2022-06-01',
            'observacion' => 'observacion de prueba invalida',
            'direccion' => 'direccion de prueba invalida',
            'departamento' => 'departamento de prueba invalida',
            'municipalidad' => 'municipalidad de prueba invalida',
            'latitud' => '13.345678',
            'longitud' => '93.765432',
        ];
        return [
            'fecha missing' => [
                array_diff_key($invalidData, ['fecha' => '']),
                'fecha',
                'The fecha field is required.'
            ],
            'observacion missing' => [
                array_diff_key($invalidData, ['observacion' => '']),
                'observacion',
                'The observacion field is required.'
            ],
            'direccion missing' => [
                array_diff_key($invalidData, ['direccion' => '']),
                'direccion',
                'The direccion field is required.'
            ],
            'departamento missing' => [
                array_diff_key($invalidData, ['departamento' => '']),
                'departamento',
                'The departamento field is required.'
            ],
            'municipalidad missing' => [ 
                array_diff_key($invalidData, ['municipalidad' => '']),
                'municipalidad',
                'The municipalidad field is required.'
            ],
            'latitud missing' => [
                array_diff_key($invalidData, ['latitud' => '']),
                'latitud',
                'The latitud field is required.'
            ],
            'longitud missing' => [
                array_diff_key($invalidData, ['longitud' => '']),
                'longitud',
                'The longitud field is required.'
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidConfiscationDataProvider')]
    public function status_422_to_store_confiscation(array $data, string $error_field, string|array $error_message): void
    {
        $response = $this->postJson(route('confiscation.store'), $data);
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
    public function unauthenticated_store_confiscation_returns_401(): void
    {
        Auth::logout();

        $payload = [
            'date' => '2024-06-01',
            'observation' => 'observacion de prueba',
            'direction' => 'direccion de prueba',
            'department' => 'departamento de prueba',
            'municipality' => 'municipalidad de prueba',
            'latitude' => '12.345678',
            'length' => '98.765432',
        ];

        $response = $this->postJson(route('confiscation.store'), $payload);
        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
