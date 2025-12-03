<?php

declare(strict_types=1);

namespace Tests\Unit\ApiResponser;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;  // ✔ correcto
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiResponserProxy;
use Tests\UnitTestCase;

class CacheResponseTest extends UnitTestCase
{   
    #[Test]
    public function cache_response_generates_key_without_params()
    {
        $this->app->instance('request', Request::create('/test', 'GET'));

        Cache::shouldReceive('remember')
            ->once()
            ->with('http://localhost/test?', 0.5, Mockery::type('Closure'))
            ->andReturn('cached-value');

        $proxy = new ApiResponserProxy();

        $this->assertEquals('cached-value', $proxy->callCache('cached-value'));
    }

    #[Test]
    public function cache_response_sorts_query_params()
    {
        $this->app->instance('request', Request::create('/test?b=2&a=1', 'GET'));

        Cache::shouldReceive('remember')
            ->once()
            ->with('http://localhost/test?a=1&b=2', 0.5, \Mockery::type('Closure'))
            ->andReturn('value');

        $proxy = new ApiResponserProxy();

        $this->assertEquals('value', $proxy->callCache('value'));
    }

    #[Test]
    public function cache_response_returns_cached_value_if_exists()
    {
        $this->app->instance('request', Request::create('/test?x=1', 'GET'));

        Cache::shouldReceive('remember')
            ->once()
            ->with('http://localhost/test?x=1', 0.5, Mockery::type('Closure'))
            ->andReturn('cached');

        $proxy = new ApiResponserProxy();

        $this->assertEquals('cached', $proxy->callCache('ignored value'));
    }
    #[Test]
    public function cache_response_stores_value_if_not_cached()
    {
        $this->app->instance('request', Request::create('/test', 'GET'));

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $closure) {
                return $closure(); // ejecuta el callback
            });

        $proxy = new ApiResponserProxy();

        $this->assertEquals('fresh-value', $proxy->callCache('fresh-value'));
    }
}
