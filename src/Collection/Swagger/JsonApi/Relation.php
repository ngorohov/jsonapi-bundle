<?php

namespace Paknahad\JsonApiBundle\Collection\Swagger\JsonApi;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Paknahad\JsonApiBundle\JsonApiStr;

class Relation extends AttributeAbstract
{
    const RELATIONS_SUFFIX = 'relation';

    public function toArray()
    {
        $array = [
            'type' => [
                'type' => 'string',
                'enum' => [$this->getType()],
                'example' => $this->getType(),
            ],
            'id' => [
                'type' => 'string',
                'format' => 'uuid',
                'description' => $this->getIdName(),
                'example' => 'e0358a0e-e8bf-4251-a09d-3e1e75ae97ab',
            ],
        ];

        return $array;
    }

    public function getDefinitionPath()
    {
        if (\in_array($this->get('type'), [ClassMetadataInfo::TO_MANY, ClassMetadataInfo::MANY_TO_MANY, ClassMetadataInfo::ONE_TO_MANY])) {
            $relation = [
                $this->get('fieldName') => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/definitions/'.$this->generateName()],
                ],
            ];
        } else {
            $relation = [$this->get('fieldName') => ['$ref' => '#/definitions/'.$this->generateName()]];
        }

        return $relation;
    }

    protected function getType()
    {
        $class = $this->get('targetEntity');

        if (defined($class . '::TYPE')) {
            return $class::TYPE;
        }

        return JsonApiStr::entityNameToType($class);
    }

    public function generateName()
    {
        $class = $this->get('targetEntity');

        if (defined($class . '::TYPE')) {
            return $class::TYPE . '.' . self::RELATIONS_SUFFIX;
        }

        return JsonApiStr::singularizeClassName($this->get('targetEntity')). '_' . self::RELATIONS_SUFFIX;
    }

    /**
     * @return string
     */
    protected function getIdName(): string
    {
        $class = $this->get('targetEntity');

        if (defined($class . '::TYPE')) {
            return $class::TYPE . '.id';
        }

        return JsonApiStr::singularizeClassName($class) . ' ID';
    }
}
