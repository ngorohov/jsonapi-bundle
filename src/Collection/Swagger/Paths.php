<?php

namespace Paknahad\JsonApiBundle\Collection\Swagger;

use Paknahad\JsonApiBundle\Collection\Swagger\JsonApi\Request;
use Paknahad\JsonApiBundle\Collection\Swagger\JsonApi\Response;
use Paknahad\JsonApiBundle\JsonApiStr;

class Paths
{
    public static function buildPaths(array $actions, string $entityType, string $route, Attributes $attributes): array
    {
        $paths = [];

        foreach ($actions as $name => $action) {
            $path = self::generateUrl($route, $name, $entityType);

            $paths[$path][strtolower($action['method'])] = [
                'tags' => [$entityType],
                'summary' => $action['title'],
                'operationId' => $entityType.'.'.$name,
                'produces' => ['application/json'],
                'parameters' => (new Request($entityType, $attributes, $name, $route))->toArray(),
                'responses' => [
                    '200' => [
                        'description' => 'successful operation',
                        'schema' => (new Response($entityType, $attributes, $name, $route))->toArray(),
                    ],
                ],
            ];
        }

        return $paths;
    }

    private static function generateUrl($baseRoute, $actionName, $entityType)
    {
        return $baseRoute.'/'.
            (
                \in_array($actionName, ['edit', 'delete', 'view']) ?
                    JsonApiStr::genEntityIdName($entityType, true) : ''
            );
    }
}
