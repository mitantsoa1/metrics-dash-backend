<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/** Documentation Swagger : les routes écrites à la main doivent y figurer. */
class OpenApiDocumentationTest extends TestCase
{
    /** @return array<string, mixed> */
    private function spec(): array
    {
        $response = $this->get('/docs.jsonopenapi')->assertOk();

        return json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_swagger_ui_is_served(): void
    {
        $this->get('/docs')->assertOk();
    }

    public function test_hand_written_routes_are_documented(): void
    {
        $paths = $this->spec()['paths'];

        $expected = [
            '/api/events' => ['post'],
            '/api/v1/events' => ['post'],
            '/api/v1/sdk/verify' => ['get'],
            '/api/v1/metrics/filter-options' => ['get'],
            '/api/v1/reports/download' => ['get'],
            '/api/v1/reports' => ['get', 'post'],
            '/api/v1/reports/{id}/download' => ['get'],
            '/api/v1/reports/{id}' => ['delete'],
            '/api/v1/scheduled-reports' => ['get', 'post'],
            '/api/v1/scheduled-reports/{id}' => ['get', 'put', 'patch', 'delete'],
            '/api/v1/scheduled-reports/{id}/test' => ['post'],
            '/api/v1/support/tickets' => ['post'],
            '/api/v1/admin/support/tickets' => ['get'],
            '/api/v1/admin/support/tickets/{id}' => ['get', 'patch', 'delete'],
        ];

        foreach ($expected as $path => $methods) {
            $this->assertArrayHasKey($path, $paths, "$path manque dans la documentation");
            foreach ($methods as $method) {
                $this->assertArrayHasKey($method, $paths[$path], strtoupper($method)." $path manque");
            }
        }
    }

    public function test_every_documented_route_really_exists(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->flatMap(fn ($route) => collect($route->methods())->map(fn ($m) => strtolower($m).' /'.ltrim($route->uri(), '/')))
            ->all();

        foreach (\App\OpenApi\CustomRoutes::operations() as $op) {
            foreach ((array) $op['path'] as $path) {
                $this->assertContains(strtolower($op['method']).' '.$path, $routes, "{$op['method']} $path est documentée mais n'existe pas");
            }
        }
    }

    public function test_push_event_documents_its_auth_codes_and_idempotency(): void
    {
        $post = $this->spec()['paths']['/api/events']['post'];

        $this->assertSame([['pushKey' => []]], $post['security']);
        $this->assertEqualsCanonicalizing(['202', '400', '401', '413', '429'], array_keys($post['responses']));
        $this->assertContains('Idempotency-Key', array_column($post['parameters'], 'name'));
    }

    public function test_security_schemes_and_public_route(): void
    {
        $spec = $this->spec();

        $this->assertEqualsCanonicalizing(['bearerAuth', 'pushKey', 'sdkKey'], array_keys($spec['components']['securitySchemes']));
        $this->assertSame([], $spec['paths']['/api/v1/support/tickets']['post']['security']); // route publique
    }

    public function test_tags_are_not_duplicated(): void
    {
        $names = array_column($this->spec()['tags'], 'name');

        $this->assertSame(array_values(array_unique($names)), $names);
    }
}
