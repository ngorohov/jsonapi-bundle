<?php

namespace Paknahad\JsonApiBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Common\Inflector\Inflector;
use Paknahad\JsonApiBundle\Collection\PostmanCollectionGenerator;
use Paknahad\JsonApiBundle\Collection\SwaggerCollectionGenerator;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;

/**
 * @author Hamid Paknahad <hp.paknahad@gmail.com>
 */
final class ApiDoc extends AbstractMaker
{
    private $postmanGenerator;

    private $swaggerGenerator;

    private $doctrineHelper;

    public function __construct(PostmanCollectionGenerator $postmanGenerator, SwaggerCollectionGenerator $swaggerGenerator, DoctrineHelper $doctrineHelper)
    {
        $this->postmanGenerator = $postmanGenerator;
        $this->swaggerGenerator = $swaggerGenerator;
        $this->doctrineHelper = $doctrineHelper;
    }

    public static function getCommandName(): string
    {
        return 'make:api:doc';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates CRUD API for Doctrine entity class')
            ->addArgument(
                'entity-class',
                InputArgument::OPTIONAL,
                sprintf('The class name of the entity to create API (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm()))
            );

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entities = [];

        if ($input->getArgument('entity-class')) {
            $entity = Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete());

            $entities[] = $entity;
        } else {
            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();
        }

        foreach ($entities as $entity) {
            $this->writeDoc($entity, $generator);
        }

        $this->writeSuccessMessage($io);

        $io->text(
            sprintf(
                'Next: Use Postman_Collection.json to test your API. You can find that in <fg=yellow>%s</>',
                PostmanCollectionGenerator::POSTMAN_PATH
            )
        );
    }

    private function writeDoc($entity, Generator $generator)
    {
        $entityClassDetails = $generator->createClassNameDetails($entity, 'Entity\\');

        $entityMetadata = $this->doctrineHelper->getMetadata($entityClassDetails->getFullName());

        $entityVarPlural = Inflector::pluralize($entityClassDetails->getShortName());

        $routePath = Str::asRoutePath($entityVarPlural);

        $this->postmanGenerator->generateCollection($entityMetadata, $entityClassDetails->getShortName(), $routePath);

        $this->swaggerGenerator->generateCollection($entityMetadata, $entityClassDetails->getShortName(), $routePath);
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'annotations'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm-pack'
        );
    }
}
