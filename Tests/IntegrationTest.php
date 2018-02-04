<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Addiks\RDMBundle\Doctrine\EventListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Doctrine\ORM\Configuration;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use ReflectionClass;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Common\EventManager;
use ReflectionProperty;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Psr\Cache\CacheItemInterface;
use Addiks\RDMBundle\Mapping\Annotation\Choice;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Query\Expr;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This is an integration-test that test's the bundle as a whole.
 * It loads all the service-definitions from the service.xml and attempts to interact with the whole object-graph as it
 * will be for the end-user. Mostly it just pretends to be doctrine, provides events to the event-listener and checks
 * that the expected things happened.
 */
final class IntegrationTest extends TestCase
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var MappingDriver
     */
    private $doctrineMetadataDriver;

    /**
     * @var Configuration
     */
    private $doctrineConfiguration;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    /**
     * @var XmlFileLoader
     */
    private $loader;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->annotationReader = $this->createMock(AnnotationReader::class);
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->doctrineMetadataDriver = $this->createMock(MappingDriver::class);
        $this->doctrineConfiguration = $this->createMock(Configuration::class);
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);

        $this->entityManager->method('getConfiguration')->willReturn($this->doctrineConfiguration);
        $this->entityManager->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        $this->cacheItemPool->method('getItem')->willReturn($this->createMock(CacheItemInterface::class));

        $this->container->set('kernel', $this->kernel);
        $this->container->set('doctrine.orm.entity_manager', $this->entityManager);
        $this->container->set('annotation_reader', $this->annotationReader);
        $this->container->set('cache.app', $this->cacheItemPool);

        $this->loader = new XmlFileLoader(
            $this->container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
    }

    /**
     * @test
     */
    public function willCreateTheNeededAdditionalSchema()
    {
        $annotationMap = array(
            'id' => [
                Id::class => new Id(),
                Column::class => $this->createColumn("id")
            ],
            'foo' => [
                Choice::class => $this->createChoice("lorem"),
            ],
            'bar' => [
                Choice::class => $this->createChoice(),
            ],
            'baz' => [],
        );

        $this->configureMockedAnnotationReader($this->annotationReader, $annotationMap);

        /** @var EventListener $eventListener */
        $eventListener = $this->spawnEventListener();

        $metadataFactory = new ClassMetadataFactory();
        $metadataFactory->setEntityManager($this->entityManager);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadataFactory->getMetadataFor(EntityExample::class);

        $classTable = new Table("some_table");
        $schema = new Schema([$classTable]);

        $eventListener->postGenerateSchemaTable(new GenerateSchemaTableEventArgs(
            $classMetadata,
            $schema,
            $classTable
        ));

        $this->assertTrue($classTable->hasColumn("lorem"));
        $this->assertTrue($classTable->hasColumn("bar"));
    }

    /**
     * @test
     */
    public function canHydrateAnEntityViaSimpleDataLoading()
    {
        $annotationMap = array(
            'id' => [
                Id::class => new Id(),
                Column::class => $this->createColumn("id")
            ],
            'foo' => [
                Service::class => $this->createService("a_service"),
            ],
            'bar' => [
                Service::class => $this->createService("b_service"),
            ],
            'baz' => [],
        );

        $this->configureMockedAnnotationReader($this->annotationReader, $annotationMap);

        /** @var EventListener $eventListener */
        $eventListener = $this->spawnEventListener();

        $metadataFactory = new ClassMetadataFactory();
        $metadataFactory->setEntityManager($this->entityManager);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadataFactory->getMetadataFor(EntityExample::class);

        $serviceA = new ServiceExample("lorem", 123);
        $serviceB = new ServiceExample("ipsum", 456);

        $entity = new EntityExample();

        $this->container->set('a_service', $serviceA);
        $this->container->set('b_service', $serviceB);

        $eventListener->loadClassMetadata(new LoadClassMetadataEventArgs($classMetadata, $this->entityManager));

        $eventListener->postLoad(new LifecycleEventArgs($entity, $this->entityManager));

        $this->assertSame($serviceA, $entity->foo);
        $this->assertSame($serviceB, $entity->bar);
    }

    /**
     * @test
     */
    public function willRecogniseFailedAssertions()
    {
        $annotationMap = array(
            'id' => [
                Id::class => new Id(),
                Column::class => $this->createColumn("id")
            ],
            'foo' => [
                Service::class => $this->createService("a_service"),
            ],
            'bar' => [
                Service::class => $this->createService("b_service"),
            ],
            'baz' => [],
        );

        $this->configureMockedAnnotationReader($this->annotationReader, $annotationMap);

        /** @var EventListener $eventListener */
        $eventListener = $this->spawnEventListener();

        $metadataFactory = new ClassMetadataFactory();
        $metadataFactory->setEntityManager($this->entityManager);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadataFactory->getMetadataFor(EntityExample::class);

        $serviceA = new ServiceExample("lorem", 123);
        $serviceB = new ServiceExample("ipsum", 456);

        $entity = new EntityExample();
        $entity->foo = $serviceA;
        $entity->bar = null;

        $this->container->set('a_service', $serviceA);
        $this->container->set('b_service', $serviceB);

        $eventListener->loadClassMetadata(new LoadClassMetadataEventArgs($classMetadata, $this->entityManager));

        $this->expectException(FailedRDMAssertionExceptionInterface::class);

        $eventListener->prePersist(new LifecycleEventArgs($entity, $this->entityManager));
    }

    /**
     * @test
     */
    public function canStoreAdditionalData()
    {
        $annotationMap = array(
            'id' => [
                Id::class => new Id(),
                Column::class => $this->createColumn("id")
            ],
            'foo' => [
            ],
            'bar' => [
                Choice::class => $this->createChoice("bar_column", [
                    'a' => $this->createService("a_service"),
                    'b' => $this->createService("b_service"),
                ]),
            ],
            'baz' => [],
        );

        $this->configureMockedAnnotationReader($this->annotationReader, $annotationMap);

        /** @var EventListener $eventListener */
        $eventListener = $this->spawnEventListener();

        $metadataFactory = new ClassMetadataFactory();
        $metadataFactory->setEntityManager($this->entityManager);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadataFactory->getMetadataFor(EntityExample::class);
        $classMetadata->table = ['name' => 'some_table'];

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        /** @var Connection $connection */
        $connection = $this->createMock(Connection::class);

        $this->entityManager->method("getUnitOfWork")->willReturn($unitOfWork);
        $this->entityManager->method("getConnection")->willReturn($connection);
        $this->entityManager->method("getClassMetadata")->will($this->returnValueMap([
            [EntityExample::class, $classMetadata]
        ]));

        $serviceA = new ServiceExample("lorem", 123);
        $serviceB = new ServiceExample("ipsum", 456);

        $entity = new EntityExample();
        $entity->foo = null;
        $entity->bar = $serviceA;

        $unitOfWork->method('getIdentityMap')->willReturn([
            EntityExample::class => [
                $entity
            ]
        ]);

        $this->container->set('a_service', $serviceA);
        $this->container->set('b_service', $serviceB);

        $connection->expects($this->once())->method("update")->with(
            $this->equalTo("some_table"),
            $this->equalTo([
                'bar_column' => 'a'
            ])
        );

        $eventListener->loadClassMetadata(new LoadClassMetadataEventArgs($classMetadata, $this->entityManager));

        $eventListener->onFlush(new OnFlushEventArgs($this->entityManager));
    }

    /**
     * @test
     */
    public function completesAWholeLifetime()
    {
        ### PREPARE

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        /** @var Connection $connection */
        $connection = $this->createMock(Connection::class);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);

        /** @var Expr $expr */
        $expr = $this->createMock(Expr::class);

        /** @var Statement $statement */
        $statement = $this->createMock(Statement::class);

        /** @var mixed $annotationMap */
        $annotationMap = array(
            'id' => [
                Id::class => new Id(),
                Column::class => $this->createColumn("id")
            ],
            'foo' => [
                Choice::class => $this->createChoice("foo_column", [
                    'a' => $this->createService("a_service"),
                    'b' => $this->createService("b_service"),
                ]),
            ],
            'bar' => [
                Choice::class => $this->createChoice("bar_column", [
                    'a' => $this->createService("a_service"),
                    'b' => $this->createService("b_service"),
                ]),
            ],
            'baz' => [],
        );

        $this->configureMockedAnnotationReader($this->annotationReader, $annotationMap);

        /** @var EventListener $eventListener */
        $eventListener = $this->spawnEventListener();

        $metadataFactory = new ClassMetadataFactory();
        $metadataFactory->setEntityManager($this->entityManager);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadataFactory->getMetadataFor(EntityExample::class);
        $classMetadata->table = ['name' => 'some_table'];

        $this->entityManager->method("getUnitOfWork")->willReturn($unitOfWork);
        $this->entityManager->method("getConnection")->willReturn($connection);
        $this->entityManager->method("getClassMetadata")->will($this->returnValueMap([
            [EntityExample::class, $classMetadata]
        ]));

        $connection->method('createQueryBuilder')->willReturn($queryBuilder);

        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('execute')->willReturn($statement);

        $queryBuilder->method('from');
        $queryBuilder->method('andWhere');
        $queryBuilder->method('addSelect');

        $statement->method("fetch")->willReturn([
            'foo_column' => null,
            'bar_column' => 'a'
        ]);

        $classTable = new Table("some_table");
        $schema = new Schema([$classTable]);

        $serviceA = new ServiceExample("lorem", 123);
        $serviceB = new ServiceExample("ipsum", 456);

        $this->container->set('a_service', $serviceA);
        $this->container->set('b_service', $serviceB);

        ### EXECUTE

        $eventListener->loadClassMetadata(new LoadClassMetadataEventArgs(
            $classMetadata,
            $this->entityManager
        ));

        $eventListener->postGenerateSchemaTable(new GenerateSchemaTableEventArgs(
            $classMetadata,
            $schema,
            $classTable
        ));

        $this->assertTrue($classTable->hasColumn("foo_column"));
        $this->assertTrue($classTable->hasColumn("bar_column"));

        $entity = new EntityExample();
        $entity->foo = null;
        $entity->bar = $serviceA;

        $eventListener->prePersist(new LifecycleEventArgs(
            $entity,
            $this->entityManager
        ));

        $unitOfWork->method('getIdentityMap')->willReturn([
            EntityExample::class => [
                $entity
            ]
        ]);

        $eventListener->onFlush(new OnFlushEventArgs(
            $this->entityManager
        ));

        $loadedEntity = new EntityExample();

        $eventListener->postLoad(new LifecycleEventArgs(
            $loadedEntity,
            $this->entityManager
        ));

        $this->assertSame($serviceA, $loadedEntity->bar);
    }

    private function spawnEventListener(): EventListener
    {
        # The test-method may replace the doctrineMetadataDriver, so redefine the return-value of getMetadataDriverImpl
        $this->doctrineConfiguration->method('getMetadataDriverImpl')->willReturn($this->doctrineMetadataDriver);

        $this->loader->load('services.xml');

        /** @var MappingDriverInterface $mappingDriver */
        $mappingDriver = $this->container->get("addiks_rdm.mapping.driver");

        $this->assertInstanceOf(MappingDriverInterface::class, $mappingDriver);

        /** @var EventListener $eventListener */
        $eventListener = $this->container->get("addiks_rdm.listener");

        $this->assertInstanceOf(EventListener::class, $eventListener);

        return $eventListener;
    }

    private function configureMockedAnnotationReader(AnnotationReader $annotationReader, array $annotationMap)
    {
        $annotationReader->method('getClassAnnotations')->will($this->returnCallback(
            function (ReflectionClass $reflectionClass) {
                if ($reflectionClass->getName() === EntityExample::class) {
                    return [new Entity()];
                }
            }
        ));

        $annotationReader->method('getPropertyAnnotation')->will($this->returnCallback(
            function (ReflectionProperty $reflectionProperty, string $annotationName) use ($annotationMap) {
                /** @var ReflectionClass $reflectionClass */
                $reflectionClass = $reflectionProperty->getDeclaringClass();

                if ($reflectionClass->getName() === EntityExample::class) {
                    if (isset($annotationMap[$reflectionProperty->getName()])) {
                        $annotationMap = $annotationMap[$reflectionProperty->getName()];
                    }

                    if (isset($annotationMap[$annotationName])) {
                        return $annotationMap[$annotationName];
                    }
                }
            }
        ));

        $annotationReader->method('getPropertyAnnotations')->will($this->returnCallback(
            function (ReflectionProperty $reflectionProperty) use ($annotationMap) {
                /** @var ReflectionClass $reflectionClass */
                $reflectionClass = $reflectionProperty->getDeclaringClass();

                if ($reflectionClass->getName() === EntityExample::class) {
                    if (isset($annotationMap[$reflectionProperty->getName()])) {
                        return array_values($annotationMap[$reflectionProperty->getName()]);

                    } else {
                        return [];
                    }
                }
            }
        ));

        $annotationDriver = new AnnotationDriver($this->annotationReader);

        $this->doctrineMetadataDriver = new MappingDriverChain();
        $this->doctrineMetadataDriver->addDriver($annotationDriver, "Addiks\\RDMBundle\\Tests");

    }

    private function createColumn(string $columnName): Column
    {
        $column = new Column();
        $column->name = $columnName;

        return $column;
    }

    private function createService(string $id): Service
    {
        $service = new Service();
        $service->id = $id;

        return $service;
    }

    private function createChoice(string $columnName = null, array $choices = array()): Choice
    {
        $choice = new Choice();
        $choice->column = $columnName;
        $choice->choices = $choices;

        return $choice;
    }

}
