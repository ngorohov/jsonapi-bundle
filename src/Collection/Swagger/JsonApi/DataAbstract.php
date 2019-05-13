<?php

namespace Paknahad\JsonApiBundle\Collection\Swagger\JsonApi;

use Paknahad\JsonApiBundle\Collection\Swagger\Attributes;
use Paknahad\JsonApiBundle\JsonApiStr;

abstract class DataAbstract
{
    /** @var Attributes */
    private $attributes;

    protected $entityType;
    protected $actionName;
    protected $route;

    public function __construct(string $entityType, Attributes $attributes, string $actionName, string $route)
    {
        $this->entityType = $entityType;
        $this->actionName = $actionName;
        $this->attributes = $attributes;
        $this->route = $route;
    }

    abstract public function toArray(): array;

    protected function genJsonApiDataBody(bool $containId = false): array
    {
        if ($containId) {
            $idProperties = [
                'id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                    'example' => 'e0358a0e-e8bf-4251-a09d-3e1e75ae97ab',
                ],
            ];
        } else {
            $idProperties = [];
        }

        return [
            'type' => 'object',
            'properties' => array_merge(
                $idProperties,
                [
                    'type' => ['type' => 'string', 'example' => $this->entityType],
                    'attributes' => ['$ref' => '#/definitions/'.$this->entityType],
                    'relationships' => $this->attributes->getRelations(),
                ]
            ),
        ];
    }
}
