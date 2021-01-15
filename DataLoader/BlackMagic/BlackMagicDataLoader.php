<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\DataLoader\BlackMagic;

use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use ReflectionObject;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use ReflectionProperty;
use Webmozart\Assert\Assert;
use ReflectionClass;
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicColumnReflectionPropertyMock;
use Addiks\RDMBundle\Hydration\HydrationContext;
use Doctrine\DBAL\Types\Type;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicReflectionServiceDecorator;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This data-loader works by injecting fake doctrine columns into the doctrine class-metadata instance(s), where the injected
 * Reflection* objects are replaced by custom mock objects that give the raw DB data from doctrine to this data-loader.
 * From doctrine's point of view, every database-column looks like an actual property on the entity, even if that property does
 * not actually exist.
 *
 * ... In other words: BLACK MAGIC!!! *woooo*
 *
 *  #####################################################################################
 *  ### WARNING: Be aware that this data-loader is considered EXPERIMENTAL!           ###
 *  ###          If you use this data-loader and bad things happen, it is YOUR FAULT! ###
 *  #####################################################################################
 *
 */
class BlackMagicDataLoader implements DataLoaderInterface
{

    /** @var MappingDriverInterface */
    private $mappingDriver;

    /** @var array<string, mixed>|null */
    private $entityDataCached;

    /** @var array<string, Column>|null */
    private $dbalColumnsCached;

    /** @var object|null */
    private $entityDataCacheSource;

    public function __construct(MappingDriverInterface $mappingDriver)
    {
        $this->mappingDriver = $mappingDriver;
    }

    public function loadDBALDataForEntity($entity, EntityManagerInterface $entityManager): array
    {
        /** @var array<string, string> $dbalData */
        $dbalData = array();

        /** @var string $className */
        $className = get_class($entity);

        if (class_exists(ClassUtils::class)) {
            $className = ClassUtils::getRealClass($className);
        }

        /** @var ClassMetadata $classMetaData */
        $classMetaData = $entityManager->getClassMetadata($className);

        /** @var ?EntityMappingInterface $entityMapping */
        $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($className);

        if ($entityMapping instanceof EntityMappingInterface) {
            /** @var array<Column> $columns */
            $columns = $entityMapping->collectDBALColumns();

            if (!empty($columns)) {
                /** @var UnitOfWork $unitOfWork */
                $unitOfWork = $entityManager->getUnitOfWork();

                /** @var array<string, mixed> $originalEntityData */
                $originalEntityData = $unitOfWork->getOriginalEntityData($entity);

                /** @var Column $column */
                foreach ($columns as $column) {
                    /** @var string $columnName */
                    $columnName = $column->getName();

                    /** @var string $fieldName */
                    $fieldName = $this->columnToFieldName($column);

                    if (isset($originalEntityData[$fieldName])) {
                        $dbalData[$columnName] = $originalEntityData[$fieldName];
                    }
                }
            }
        }

        return $dbalData;
    }

    public function storeDBALDataForEntity($entity, EntityManagerInterface $entityManager)
    {
        # This happens after doctrine has already UPDATE'd the row itself, do nothing here.
    }

    public function removeDBALDataForEntity($entity, EntityManagerInterface $entityManager)
    {
        # Doctrine DELETE's the row for us, we dont need to do anything here.
    }

    public function prepareOnMetadataLoad(EntityManagerInterface $entityManager, ClassMetadata $classMetadata)
    {
        /** @var ClassMetadataFactory $metadataFactory */
        $metadataFactory = $entityManager->getMetadataFactory();

        if ($metadataFactory instanceof AbstractClassMetadataFactory) {
            /** @var ReflectionService $reflectionService */
            $reflectionService = $metadataFactory->getReflectionService();

            if (!$reflectionService instanceof BlackMagicReflectionServiceDecorator) {
                $reflectionService = new BlackMagicReflectionServiceDecorator(
                    $reflectionService,
                    $this->mappingDriver,
                    $entityManager,
                    $this
                );

                $metadataFactory->setReflectionService($reflectionService);
            }
        }

        /** @var EntityMappingInterface|null $entityMapping */
        $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($classMetadata->getName());

        if ($entityMapping instanceof EntityMappingInterface) {
            /** @var array<Column> $dbalColumns */
            $dbalColumns = $entityMapping->collectDBALColumns();

            /** @var Column $column */
            foreach ($dbalColumns as $column) {
                /** @var string $columnName */
                $columnName = $column->getName();

                /** @var string $fieldName */
                $fieldName = $this->columnToFieldName($column);

                if (!isset($classMetadata->reflFields[$fieldName])) {
                    $classMetadata->reflFields[$fieldName] = new BlackMagicColumnReflectionPropertyMock(
                        $entityManager,
                        $classMetadata,
                        $column,
                        $fieldName,
                        $this
                    );
                }

                if (!isset($classMetadata->fieldMappings[$fieldName])) {
                    /** @var array<string, mixed> $mapping */
                    $mapping = array_merge(
                        $column->toArray(),
                        [
                            'columnName' => $columnName,
                            'fieldName' => $fieldName,
                        ]
                    );

                    if (isset($mapping['type']) && $mapping['type'] instanceof Type) {
                        $mapping['type'] = $mapping['type']->getName();
                    }

                    #$classMetadata->mapField($mapping);
                    $classMetadata->fieldMappings[$fieldName] = $mapping;
                }

                if (!isset($classMetadata->fieldNames[$columnName])) {
                    $classMetadata->fieldNames[$columnName] = $fieldName;
                }

                if (!isset($classMetadata->columnNames[$fieldName])) {
                    $classMetadata->columnNames[$fieldName] = $columnName;
                }
            }
        }
    }

    public function onColumnValueSetOnEntity(
        EntityManagerInterface $entityManager,
        ?object $entity,
        string $columnName,
        $value = null
    ): void {
        # Do nothing here, we first let doctrine collect all the data and the use that in "loadDBALDataForEntity" above.
    }

    public function onColumnValueRequestedFromEntity(
        EntityManagerInterface $entityManager,
        $entity,
        string $columnName
    ) {
        /** @var array<string, mixed> $entityData */
        $entityData = array();

        /** @var array<string, Column> $dbalColumns */
        $dbalColumns = array();

        if (is_object($entity)) {
            if ($entity === $this->entityDataCacheSource) {
                # This caching mechanism stores only the data of the current entity
                # and relies on doctrine only reading one entity at a time.
                $entityData = $this->entityDataCached;
                $dbalColumns = $this->dbalColumnsCached;

            } else {
                /** @var string $className */
                $className = get_class($entity);

                if (class_exists(ClassUtils::class)) {
                    $className = ClassUtils::getRealClass($className);
                }

                /** @var EntityMappingInterface|null $entityMapping */
                $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($className);

                if ($entityMapping instanceof EntityMappingInterface) {
                    $context = new HydrationContext($entity, $entityManager);

                    $entityData = $entityMapping->revertValue($context, $entity);

                    /** @var Column $column */
                    foreach ($entityMapping->collectDBALColumns() as $column) {
                        $dbalColumns[$column->getName()] = $column;
                    }
                }

                $this->entityDataCached = $entityData;
                $this->entityDataCacheSource = $entity;
                $this->dbalColumnsCached = $dbalColumns;
            }
        }

        $value = $entityData[$columnName] ?? null;

        if (!is_null($value) && isset($dbalColumns[$columnName])) {
            /** @var AbstractPlatform $platform */
            $platform = $entityManager->getConnection()->getDatabasePlatform();

            /** @var Column $column */
            $column = $dbalColumns[$columnName];

            $value = $column->getType()->convertToPHPValue($value, $platform);
        }

        return $value;
    }

    public function columnToFieldName(Column $column): string
    {
        /** @var string $columnName */
        $columnName = $column->getName();

        /** @var string $fieldName */
        $fieldName = '__COLUMN__' . $columnName;

        return $fieldName;
    }

}