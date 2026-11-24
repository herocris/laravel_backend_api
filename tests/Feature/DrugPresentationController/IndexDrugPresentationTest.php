<?php

namespace Tests\Feature\Admin\DrugPresentationController;

use App\Models\DrugPresentation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexDrugPresentationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->superAdminLogin();
        Auth::login($this->user);
        $this->grantPermissions($this->user, ['drugPresentation.view']);
    }

    #[Test]
    public function index_returns_all_drug_presentations()
    {
        DrugPresentation::factory()->count(10)->create();

        $response = $this->getJson(route('drugPresentation.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'success', 'message',
            'data' => [
                '*' => [
                    'identificador',
                    'descripcion',
                    'logo',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total', 'links'],
        ]);

        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function empty_drug_presentation_collection(): void
    {
        $response = $this->getJson(route('drugPresentation.index'));
        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401()
    {
        Auth::logout();

        $response = $this->getJson(route('drugPresentation.index'));

        $response->assertStatus(401)->assertExactJson([
            'code' => 401,
            'error' => 'No autenticado',
        ]);
    }
}
