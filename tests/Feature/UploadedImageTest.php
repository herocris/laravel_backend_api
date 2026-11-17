<?php

namespace Tests\Feature;

use App\Models\Ammunition;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\DrugPresentation;
use App\Models\User;
use App\Models\Weapon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UploadedImageTest extends TestCase
{
    public static function catalogs(): array
    {
        return ['drugPresentation' => [DrugPresentation::class, 'drugPresentation'], 'drug' => [Drug::class, 'drug'], 'weapon' => [Weapon::class, 'weapon'], 'ammunition' => [Ammunition::class, 'ammunition']];
    }

    #[DataProvider('catalogs')]
    public function test_catalog_upload_and_soft_delete_preserve_recoverable_images(string $model, string $resource): void
    {
        Storage::fake('public');
        $record = $model::factory()->create(['logo' => $resource.'/old.png']);
        Storage::disk('public')->put($record->logo, 'old');
        $user = User::factory()->create();
        $this->grantPermissions($user, [$resource.'.update', $resource.'.delete']);
        $this->actingAs($user, 'api');

        $this->putJson(route($resource.'.update', $record), ['descripcion' => 'Changed', 'logo' => UploadedFile::fake()->image('new.png')])->assertOk();
        $record->refresh();
        Storage::disk('public')->assertExists([$resource.'/old.png', $record->logo]);
        $this->deleteJson(route($resource.'.destroy', $record))->assertOk();
        $this->postJson(route($resource.'.restore', $record))->assertOk();
        Storage::disk('public')->assertExists($record->logo);
    }

    private function evidence(): DrugConfiscation
    {
        Confiscation::factory()->create();
        Drug::factory()->create();
        DrugPresentation::factory()->create();

        return DrugConfiscation::factory()->create(['photo' => 'drugConfiscation/old.png']);
    }

    private function payload(DrugConfiscation $record): array
    {
        return ['cantidad' => 2, 'peso' => 3, 'decomiso' => $record->confiscation_id, 'droga' => $record->drug_id, 'presentacion' => $record->drug_presentation_id, 'foto' => UploadedFile::fake()->image('new.png')];
    }

    public function test_evidence_replacement_is_private_and_download_requires_permission(): void
    {
        Storage::fake('evidence');
        Storage::fake('public');
        $record = $this->evidence();
        Storage::disk('evidence')->put($record->photo, 'old');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drugConfiscation.update']);
        $this->actingAs($user, 'api')->putJson(route('drugConfiscation.update', $record), $this->payload($record))->assertOk();
        $record->refresh();
        Storage::disk('evidence')->assertExists(['drugConfiscation/old.png', $record->photo]);
        Storage::disk('public')->assertEmpty();
        $this->getJson(route('drugConfiscation.photo', $record))->assertForbidden();
        $this->grantPermissions($user, ['drugConfiscation.view']);
        $this->getJson(route('drugConfiscation.photo', $record))->assertOk()->assertHeader('Content-Type', 'image/png');
        $record->delete();
        $this->getJson(route('drugConfiscation.photo', $record))->assertOk();
        $record->restore();
        Storage::disk('evidence')->assertExists($record->photo);
    }

    public function test_failed_database_update_preserves_old_photo_and_cleans_new_upload(): void
    {
        Storage::fake('evidence');
        $record = $this->evidence();
        Storage::disk('evidence')->put($record->photo, 'old');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drugConfiscation.update']);
        DB::unprepared("CREATE TRIGGER reject_photo BEFORE UPDATE ON drug_confiscations BEGIN SELECT RAISE(ABORT, 'controlled failure'); END");

        $this->actingAs($user, 'api')->putJson(route('drugConfiscation.update', $record), $this->payload($record))->assertInternalServerError();

        $this->assertSame('drugConfiscation/old.png', $record->fresh()->photo);
        $this->assertSame(['drugConfiscation/old.png'], Storage::disk('evidence')->allFiles());
    }

    public function test_failed_insert_cleans_new_upload(): void
    {
        Storage::fake('evidence');
        $record = $this->evidence();
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drugConfiscation.create']);
        DB::unprepared("CREATE TRIGGER reject_insert BEFORE INSERT ON drug_confiscations BEGIN SELECT RAISE(ABORT, 'controlled failure'); END");

        $this->actingAs($user, 'api')->postJson(route('drugConfiscation.store'), $this->payload($record))->assertInternalServerError();

        $this->assertDatabaseCount('drug_confiscations', 1);
        Storage::disk('evidence')->assertEmpty();
    }

    public function test_storage_failure_preserves_database_and_old_photo(): void
    {
        $disk = Storage::fake('evidence');
        $record = $this->evidence();
        $disk->put($record->photo, 'old');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drugConfiscation.update']);
        $mock = \Mockery::mock($disk)->makePartial();
        $mock->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('evidence', $mock);

        $this->actingAs($user, 'api')->putJson(route('drugConfiscation.update', $record), $this->payload($record))->assertInternalServerError();

        $this->assertSame('drugConfiscation/old.png', $record->fresh()->photo);
        $disk->assertExists('drugConfiscation/old.png');
    }

    public function test_guest_cannot_download_evidence(): void
    {
        $this->getJson(route('drugConfiscation.photo', 123))->assertUnauthorized();
    }

    public function test_legacy_public_photo_is_not_served_by_private_endpoint(): void
    {
        Storage::fake('evidence');
        Storage::fake('public');
        $record = $this->evidence();
        Storage::disk('public')->put($record->photo, 'legacy');
        $user = User::factory()->create();
        $this->grantPermissions($user, ['drugConfiscation.view']);

        $this->actingAs($user, 'api')->getJson(route('drugConfiscation.photo', $record))->assertNotFound();
        Storage::disk('public')->assertExists($record->photo);
    }
}
