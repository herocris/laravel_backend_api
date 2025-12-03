<?php

namespace Tests\Feature\Admin\ConfiscationController;

use App\Models\Confiscation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateConfiscationTest extends TestCase
{
    private User $user;
    private Confiscation $confiscation;
    private array $updateData = [
            'fecha' => '2024-06-01',
            'observacion' => 'observacion actualizada',
            'direccion' => 'direccion actualizada',
            'departamento' => 'departamento actualizada',
            'municipalidad' => 'municipalidad actualizada',
            'latitud' => '12.345678',
            'longitud' => '98.765432',
        ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->confiscation = Confiscation::factory()->create();
        Confiscation::factory()->create();
    }

    #[Test]
    public function update_confiscation_successfully()
    {
        $response = $this->putJson(route('confiscation.update', ['confiscation' => $this->confiscation->id]), $this->updateData);

        $response->assertOk();
        $response->assertExactJson([
            'identificador' => $response->json('identificador'),
            'fecha' => $this->updateData['fecha'],
            'observacion' => $this->updateData['observacion'],
            'direccion' => $this->updateData['direccion'],
            'departamento' => $this->updateData['departamento'],
            'municipalidad' => $this->updateData['municipalidad'],
            'latitud' => $this->updateData['latitud'],
            'longitud' => $this->updateData['longitud'],
        ]);

        $this->assertDatabaseHas('confiscations', [
            'id' => $this->confiscation->id,
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
    public function status_422_to_update_confiscation(array $data, string $error_field, string|array $error_message): void
    {
        $response = $this->putJson(route('confiscation.update', ['confiscation' => $this->confiscation->id]), $data);
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

        $response = $this->putJson(route("confiscation.update", ['confiscation' => $this->confiscation->id]), $this->updateData);

        $response->assertStatus(401);
    }
}
