<?php

namespace App\Providers;

use App\Helpers\ConfiscationReadCache;
use App\Models\Ammunition;
use App\Models\AmmunitionConfiscation;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\DrugConfiscation;
use App\Models\Weapon;
use App\Models\WeaponConfiscation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Activitylog\CauserResolver;
use Tymon\JWTAuth\Http\Parser\AuthHeaders;
use Tymon\JWTAuth\Http\Parser\Cookies;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(ConfiscationReadCache $cache): void
    {
        $this->app->make(CauserResolver::class)->resolveUsing(
            fn (): ?Model => $this->app->bound('request')
                ? Auth::guard(config('activitylog.default_auth_driver'))->user()
                : null,
        );

        foreach ([Confiscation::class, Drug::class, Weapon::class, Ammunition::class,
            DrugConfiscation::class, WeaponConfiscation::class, AmmunitionConfiscation::class] as $model) {
            $model::saved($cache->invalidate(...));
            $model::deleted($cache->invalidate(...));
        }

        $this->app->make('tymon.jwt.parser')->setChain([new AuthHeaders, new Cookies(false)]);

        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(30)->by('ip:'.$request->ip()),
            Limit::perMinute(5)->by('account:'.hash('sha256', Str::lower(is_string($request->input('email')) ? $request->input('email') : '').'|'.$request->ip())),
        ]);
        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('csrf', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('session', fn (Request $request): Limit => Limit::perMinute(30)->by('user:'.$request->user()->getAuthIdentifier()));
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)->by('user:'.$request->user()->getAuthIdentifier()));
        RateLimiter::for('statistics', fn (Request $request): Limit => Limit::perMinute(10)->by('user:'.$request->user()->getAuthIdentifier()));
    }
}
