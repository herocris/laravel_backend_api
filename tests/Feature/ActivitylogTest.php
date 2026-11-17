<?php

namespace Tests\Feature;

use App\Models\Activitylog;
use App\Models\Permission;
use App\Models\User;
use Spatie\Activitylog\CauserResolver;
use Tests\TestCase;

class ActivitylogTest extends TestCase
{
    public function test_records_system_activity_without_a_request_or_authenticated_user(): void
    {
        $request = $this->app['request'];
        $this->app['auth']->forgetGuards();
        $this->app->offsetUnset('request');

        try {
            $permission = Permission::factory()->create(['name' => 'audit.original']);
            $permission->update(['name' => 'audit.changed']);
            $permission->delete();
            $permission->restore();
        } finally {
            $this->app->instance('request', $request);
        }

        foreach (['created' => 'creado', 'updated' => 'actualizado', 'deleted' => 'borrado', 'restored' => 'restaurado'] as $event => $description) {
            $this->assertDatabaseHas('activity_log', [
                'subject_type' => Permission::class,
                'subject_id' => $permission->id,
                'event' => $event,
                'description' => "Se ha {$description} el permission",
                'log_name' => 'Sistema',
                'causer_id' => null,
                'causer_type' => null,
            ]);
        }

        $update = Activitylog::where('event', 'updated')->firstOrFail();
        $this->assertSame('audit.original', $update->properties['old']['name']);
        $this->assertSame('audit.changed', $update->properties['attributes']['name']);
        $this->assertDatabaseCount('users', 0);
        $this->assertNotSoftDeleted($permission);
    }

    public function test_records_the_api_user_as_actor_and_log_name(): void
    {
        $actor = User::factory()->create(['name' => 'Audit actor']);
        $this->actingAs($actor, 'api');

        $permission = Permission::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Permission::class,
            'subject_id' => $permission->id,
            'event' => 'created',
            'log_name' => 'Audit actor',
            'causer_type' => User::class,
            'causer_id' => $actor->id,
        ]);
    }

    public function test_uses_an_explicit_background_actor_without_authenticating_it(): void
    {
        $actor = User::factory()->create(['name' => 'Background actor']);
        app(CauserResolver::class)->setCauser($actor);

        $permission = Permission::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Permission::class,
            'subject_id' => $permission->id,
            'event' => 'created',
            'log_name' => 'Background actor',
            'causer_type' => User::class,
            'causer_id' => $actor->id,
        ]);
        $this->assertGuest('api');
    }

    public function test_records_unauthenticated_user_creation_as_system_without_logging_passwords(): void
    {
        $user = User::factory()->create(['name' => 'New account', 'email' => 'audit@example.test']);

        $activity = Activitylog::where('subject_type', User::class)->where('subject_id', $user->id)->sole();

        $this->assertSame('Sistema', $activity->log_name);
        $this->assertNull($activity->causer_id);
        $this->assertSame(['attributes' => ['name' => 'New account', 'email' => 'audit@example.test']], $activity->properties->all());
    }
}
