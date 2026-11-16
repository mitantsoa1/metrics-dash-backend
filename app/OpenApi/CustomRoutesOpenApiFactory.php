<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

/**
 * Complète la documentation générée par API Platform avec les routes écrites à la main
 * (ingestion, vérification de clé SDK, rapports, filtres, support), qu'API Platform ne voit pas.
 * Les descriptions sont dans CustomRoutes ; ici on les transforme en modèle OpenAPI.
 */
class CustomRoutesOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $paths = $openApi->getPaths();
        foreach ($this->group(CustomRoutes::operations()) as $path => $byMethod) {
            $item = new PathItem;
            foreach ($byMethod as $method => $spec) {
                $item = $item->{'with'.ucfirst($method)}($this->operation($spec));
            }
            $paths->addPath($path, $item);
        }

        $components = $openApi->getComponents();
        $schemes = $components->getSecuritySchemes() ?? new ArrayObject;
        foreach (CustomRoutes::securitySchemes() as $name => $scheme) {
            $schemes[$name] = new SecurityScheme(
                type: $scheme['type'],
                description: $scheme['description'],
                name: $scheme['name'] ?? null,
                in: $scheme['in'] ?? null,
                scheme: $scheme['scheme'] ?? null,
                bearerFormat: $scheme['bearerFormat'] ?? null,
            );
        }

        $tags = $openApi->getTags();
        $known = array_map(fn (Tag $tag) => $tag->getName(), $tags);
        foreach (CustomRoutes::tags() as $name => $description) {
            // Une étiquette déjà créée par API Platform (ex. « Ingestion ») n'est pas dupliquée
            if (! in_array($name, $known, true)) {
                $tags[] = new Tag($name, $description);
            }
        }

        return $openApi->withComponents($components->withSecuritySchemes($schemes))->withTags($tags);
    }

    /** @return array<string, array<string, array<string, mixed>>> chemin => méthode => description */
    private function group(array $operations): array
    {
        $grouped = [];
        foreach ($operations as $op) {
            foreach ((array) $op['path'] as $path) {
                $grouped[$path][strtolower($op['method'])] = $op;
            }
        }

        return $grouped;
    }

    private function operation(array $spec): Operation
    {
        $parameters = [];
        foreach (($spec['params'] ?? []) as $p) {
            $parameters[] = new Parameter(
                name: $p['name'],
                in: $p['in'],
                description: $p['description'] ?? '',
                required: $p['required'] ?? ($p['in'] === 'path'),
                schema: $p['schema'] ?? ['type' => 'string'],
                example: $p['example'] ?? null,
            );
        }

        $responses = [];
        foreach ($spec['responses'] as $status => $r) {
            $content = null;
            if (isset($r['schema'])) {
                $content = new ArrayObject([($r['type'] ?? 'application/json') => new MediaType(new ArrayObject($r['schema']), $r['example'] ?? null)]);
            } elseif (isset($r['type'])) {
                $content = new ArrayObject([$r['type'] => new MediaType(new ArrayObject(['type' => 'string', 'format' => 'binary']))]);
            }
            $responses[(string) $status] = new Response($r['description'], $content);
        }

        $body = null;
        if (isset($spec['body'])) {
            $body = new RequestBody(
                $spec['body']['description'] ?? null,
                new ArrayObject(['application/json' => new MediaType(new ArrayObject($spec['body']['schema']), $spec['body']['example'] ?? null)]),
                $spec['body']['required'] ?? true,
            );
        }

        $auth = $spec['auth'] ?? null;

        return new Operation(
            operationId: $spec['id'],
            tags: [$spec['tag']],
            responses: $responses,
            summary: $spec['summary'],
            description: $spec['description'] ?? null,
            parameters: $parameters,
            requestBody: $body,
            // [] = route publique ; sinon le schéma d'authentification attendu
            security: $auth === null ? [] : [[$auth => []]],
        );
    }
}
