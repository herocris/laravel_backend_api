<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Cache\ConfiscationReadCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LazyLoadingPreventionTest extends TestCase
{
    public function test_application_boot_rejects_lazy_loading_across_a_collection(): void
    {
        User::factory()->count(2)->create();
        $users = User::all();

        $this->expectException(LazyLoadingViolationException::class);

        $users->first()->roles;
    }

    #[TestWith(['local'])]
    #[TestWith(['staging'])]
    public function test_non_production_environments_reject_lazy_loading(string $environment): void
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = $environment;

        try {
            (new AppServiceProvider($this->app))->boot(app(ConfiscationReadCache::class));
            User::factory()->count(2)->create();
            $users = User::all();
            $this->expectException(LazyLoadingViolationException::class);
            $users->first()->roles;
        } finally {
            $this->app['env'] = $originalEnvironment;
            Model::preventLazyLoading();
        }
    }

    public function test_production_keeps_lazy_loading_available(): void
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            (new AppServiceProvider($this->app))->boot(app(ConfiscationReadCache::class));
            User::factory()->count(2)->create();
            $user = User::all()->first();

            $this->assertCount(0, $user->roles);
            $this->assertTrue($user->relationLoaded('roles'));
        } finally {
            $this->app['env'] = $originalEnvironment;
            Model::preventLazyLoading();
        }
    }
}
