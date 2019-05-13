<?php

namespace Paknahad\JsonApiBundle\Collection;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Paknahad\JsonApiBundle\Collection\Swagger\Attributes;
use Paknahad\JsonApiBundle\Collection\Swagger\Paths;
use Paknahad\JsonApiBundle\Collection\Swagger\Swagger;
use Paknahad\JsonApiBundle\JsonApiStr;
use Symfony\Component\Yaml\Yaml;

class SwaggerCollectionGenerator extends CollectionGeneratorAbstract
{
    /** @var Swagger */
    private $swagger;
    /** @var Attributes */
    private $fields;

    const SWAGGER_PATH = 'collections/swagger.yaml';
    const SWAGGER_JSON_PATH = 'collections/swagger.json';
    const SWAGGER_TEMPLATE_PATH = __DIR__.'/../Resources/skeleton/swagger.yaml';

    public function generateCollection(ClassMetadata $classMetadata, string $entityName, string $route): string
    {
        $class = $classMetadata->name;

        $this->type = JsonApiStr::entityNameToType($entityName);

        if (defined($class . '::TYPE')) {
            $this->type = $class::TYPE;
        }

        $this->swagger = new Swagger($this->loadOldCollection());

        $this->fields = Attributes::parse($classMetadata);

        $this->setDefinitions($this->type);
        $this->generateAllPaths($this->type, $route);

        $this->fileManager->dumpFile(self::SWAGGER_PATH, Yaml::dump($this->swagger->toArray(), 20, 2));
        $this->fileManager->dumpFile(self::SWAGGER_JSON_PATH, json_encode($this->swagger->toArray(), JSON_PRETTY_PRINT));

        return self::SWAGGER_PATH;
    }

    private function generateAllPaths(string $entityType, string $route): void
    {
        $paths = Paths::buildPaths($this->getActionsList($entityType), $entityType, $route, $this->fields);

        foreach ($paths as $path => $content) {
            $this->swagger->addPath($path, $content);
        }
    }

    private function setDefinitions(string $entityType): void
    {
        $this->swagger->addDefinition($entityType, $this->fields->getFieldsSchema());

        foreach ($this->fields->getRelationsSchemas() as $name => $schema) {
            $this->swagger->addDefinition($name, $schema);
        }
    }

    private function loadOldCollection(): array
    {
        if (file_exists($this->rootDirectory.'/'.self::SWAGGER_PATH)) {
            $file = $this->rootDirectory.'/'.self::SWAGGER_PATH;
        } else {
            $file = self::SWAGGER_TEMPLATE_PATH;
        }

        return Yaml::parseFile($file);
    }
}
