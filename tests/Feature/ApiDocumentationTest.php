<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_generated_documentation_matches_all_api_routes_permissions_and_security_schemes(): void
    {
        Storage::fake('documentation');
        $directory = Storage::disk('documentation')->path('');
        config(['l5-swagger.defaults.paths.docs' => $directory]);
        $this->artisan('l5-swagger:generate', ['--no-interaction' => true])->assertSuccessful();
        $document = json_decode(file_get_contents($directory.'/api-docs.json'), true, flags: JSON_THROW_ON_ERROR);
        $committed = json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($committed, $document, 'Regenera el JSON versionado tras editar app/Docs.');

        $operations = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/') || ! str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $path = '/'.$route->uri();
                $verb = strtolower($method);
                $this->assertArrayHasKey($path, $document['paths']);
                $this->assertArrayHasKey($verb, $document['paths'][$path]);
                $operation = $document['paths'][$path][$verb];
                $operations[] = $method.' '.$path;
                foreach ($route->gatherMiddleware() as $middleware) {
                    if (str_starts_with($middleware, 'can:')) {
                        $this->assertSame(substr($middleware, 4), $operation['x-permission']);
                        $this->assertArrayHasKey('403', $operation['responses']);
                    }
                }
                foreach ($operation['security'] as $requirement) {
                    foreach (array_keys($requirement) as $scheme) {
                        $this->assertArrayHasKey($scheme, $document['components']['securitySchemes']);
                    }
                }
                if (in_array('auth', $route->gatherMiddleware(), true)) {
                    $this->assertContains(['bearerAuth' => []], $operation['security']);
                    $cookie = ['authCookie' => []];
                    if ($method !== 'GET') {
                        $cookie['csrfHeader'] = [];
                    }
                    $this->assertContains($cookie, $operation['security']);
                }
            }
        }

        $documented = [];
        foreach ($document['paths'] as $path => $pathItem) {
            foreach (array_keys($pathItem) as $verb) {
                if (in_array($verb, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $documented[] = strtoupper($verb).' '.$path;
                }
            }
        }
        sort($operations);
        sort($documented);
        $this->assertSame($operations, $documented);
        $this->assertSame(['/'], array_column($document['servers'], 'url'));
    }
}
