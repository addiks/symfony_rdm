<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Doctrine;

use Addiks\RDMBundle\Hydration\EntityHydratorInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\DBAL\Schema\Column;

/**
 * Hooks into the event's of doctrine2-ORM and forwards the entities to other objects.
 */
final class EventListener
{

    /**
     * @var EntityHydratorInterface
     */
    private $entityHydrator;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var DataLoaderInterface
     */
    private $dbalDataLoader;

    /**
     * @var array<string, bool>
     */
    private $hasRdmMappingForClass = array();

    public function __construct(
        EntityHydratorInterface $entityHydrator,
        MappingDriverInterface $mappingDriver,
        DataLoaderInterface $dbalDataLoader
    ) {
        $this->entityHydrator = $entityHydrator;
        $this->mappingDriver = $mappingDriver;
        $this->dbalDataLoader = $dbalDataLoader;
    }

    public function postLoad(LifecycleEventArgs $arguments): void
    {
        /** @var object $entity */
        $entity = $arguments->getEntity();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $arguments->getEntityManager();

        $this->entityHydrator->hydrateEntity($entity, $entityManager);
    }

    public function prePersist(LifecycleEventArgs $arguments): void
    {
        /** @var object $entity */
        $entity = $arguments->getEntity();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $arguments->getEntityManager();

        if (!($entity instanceof Proxy)) {
            $this->entityHydrator->assertHydrationOnEntity($entity, $entityManager);
        }
    }

    public function postFlush(PostFlushEventArgs $arguments): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $arguments->getEntityManager();

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getIdentityMap() as $className => $entities) {
            if (!$this->hasRdmMappingForClass($className)) {
                continue;
            }

            foreach ($entities as $entity) {
                /** @var object $entity */

                if (!$this->isUnitializedProxy($entity)) {
                    if ($unitOfWork->isScheduledForDelete($entity)) {
                        $this->dbalDataLoader->removeDBALDataForEntity($entity, $entityManager);

                    } else {
                        $this->dbalDataLoader->storeDBALDataForEntity($entity, $entityManager);
                    }
                }
            }
        }
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $arguments): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $arguments->getEntityManager();

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $arguments->getClassMetadata();

        $this->dbalDataLoader->prepareOnMetadataLoad($entityManager, $classMetadata);
    }

    /**
     * Invoked when doctrine has generated a table-definition in the target-schema.
     * Collects the additional schema-columns from the mapping and add's them to the table.
     *
     * Dispatched in:
     * @see SchemaTool::getSchemaFromMetadata
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $arguments): void
    {
        /** @var Table $table */
        $table = $arguments->getClassTable();

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $arguments->getClassMetadata();

        /** @var ?EntityMappingInterface $entityMapping */
        $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($classMetadata->getName());

        if ($entityMapping instanceof EntityMappingInterface) {
            /** @var array<Column> $additionalColumns */
            $additionalColumns = $entityMapping->collectDBALColumns();

            foreach ($additionalColumns as $column) {
                /** @var Column $column */

                /** @var string $columnName */
                $columnName = $column->getName();

                if (!$table->hasColumn($columnName)) {
                    $table->addColumn(
                        $columnName,
                        $column->getType()->getName(),
                        $column->toArray()
                    );
                }
            }
        }
    }

    private function hasRdmMappingForClass(string $className): bool
    {
        if (!isset($this->hasRdmMappingForClass[$className])) {
            /** @var string $currentClassName */
            $currentClassName = $className;

            do {
                $this->hasRdmMappingForClass[$className] = is_object(
                    $this->mappingDriver->loadRDMMetadataForClass($currentClassName)
                );

                $currentClassName = current(class_parents($currentClassName));
            } while (class_exists($currentClassName) && !$this->hasRdmMappingForClass[$className]);
        }

        return $this->hasRdmMappingForClass[$className];
    }

    /**
     * @param object $entity
     */
    private function isUnitializedProxy($entity): bool
    {
        /** @var bool $isUnitializedProxy */
        $isUnitializedProxy = false;

        if ($entity instanceof Proxy && !$entity->__isInitialized()) {
            $isUnitializedProxy = true;
        }

        return $isUnitializedProxy;
    }

}
