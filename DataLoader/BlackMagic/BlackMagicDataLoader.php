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
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicReflectionServiceDecorator;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\Mapping\ReflectionService;
use DateTime;

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

        /** @var class-string $className */
        $className = get_class($entity);

        if (class_exists(ClassUtils::class)) {
            $className = ClassUtils::getRealClass($className);
            Assert::classExists($className);
        }

        /** @var ClassMetadata $classMetaData */
        $classMetaData = $entityManager->getClassMetadata($className);

        /** @var EntityMappingInterface|null $entityMapping */
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

                    if (array_key_exists($fieldName, $originalEntityData)) {
                        $dbalData[$columnName] = $originalEntityData[$fieldName];

                    } elseif (array_key_exists($columnName, $originalEntityData)) {
                        $dbalData[$columnName] = $originalEntityData[$columnName];
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
            /** @var ReflectionService|null $reflectionService */
            $reflectionService = $metadataFactory->getReflectionService();

            if (!$reflectionService instanceof BlackMagicReflectionServiceDecorator) {
                $reflectionService = new BlackMagicReflectionServiceDecorator(
                    $reflectionService ?? new RuntimeReflectionService(),
                    $this->mappingDriver,
                    $entityManager,
                    $this
                );

                $metadataFactory->setReflectionService($reflectionService);
            }
        }

        /** @var class-string $className */
        $className = $classMetadata->getName();

        /** @var EntityMappingInterface|null $entityMapping */
        $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($className);

        if ($entityMapping instanceof EntityMappingInterface) {
            /** @var array<Column> $dbalColumns */
            $dbalColumns = $entityMapping->collectDBALColumns();

            /** @var Column $column */
            foreach ($dbalColumns as $column) {
                /** @var string $columnName */
                $columnName = $column->getName();

                /** @var string $fieldName */
                $fieldName = $this->columnToFieldName($column);

                /** @psalm-suppress DeprecatedProperty */
                if (isset ($classMetadata->fieldNames) && isset($classMetadata->fieldNames[$columnName])) {
                    # This is a native doctrine column, do not touch! Otherwise the column might get unwanted UPDATE's.
                    continue;
                }

                /** @psalm-suppress DeprecatedProperty */
                $classMetadata->fieldNames[$columnName] = $fieldName;

                /** @psalm-suppress DeprecatedProperty */
                if (isset ($classMetadata->columnNames) && !isset($classMetadata->columnNames[$fieldName])) {
                    $classMetadata->columnNames[$fieldName] = $columnName;
                }

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
                            'nullable' => !$column->getNotnull(),
                        ]
                    );

                    if (isset($mapping['type']) && $mapping['type'] instanceof Type) {
                        $mapping['type'] = $mapping['type']->getName();
                    }

                    #$classMetadata->mapField($mapping);
                    $classMetadata->fieldMappings[$fieldName] = $mapping;
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
            if ($entity === $this->entityDataCacheSource
            && is_array($this->entityDataCached)
            && array_key_exists($columnName, $this->entityDataCached)) {
                # This caching mechanism stores only the data of the current entity
                # and relies on doctrine only reading one entity at a time.

                $entityData = $this->entityDataCached;
                $dbalColumns = $this->dbalColumnsCached;

                unset($this->entityDataCached[$columnName]);

            } else {
                /** @var class-string $className */
                $className = get_class($entity);

                if (class_exists(ClassUtils::class)) {
                    $className = ClassUtils::getRealClass($className);
                    Assert::classExists($className);
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

            /** @var Type $type */
            $type = $column->getType();

            $value = $type->convertToPHPValue($value, $platform);

            if (is_int($value) && $type->getName() === 'string') {
                $value = (string)$value;

            } elseif ($value instanceof DateTime) {
                /** @var UnitOfWork $unitOfWork */
                $unitOfWork = $entityManager->getUnitOfWork();

                /** @var mixed $originalValue */
                $originalValue = null;

                if ($this->isDateTimeEqualToValueFromUnitOfWorkButNotSame($value, $unitOfWork, $column, $entity, $originalValue)) {
                    # Because doctrine uses '===' to compute changesets when compaing original with actual,
                    # we need to keep the identity of DateTime objects if they are actually the "same".
                    $value = $originalValue;
                }
            }
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

    private function isDateTimeEqualToValueFromUnitOfWorkButNotSame(
        DateTime $value,
        UnitOfWork $unitOfWork,
        Column $column,
        object $entity,
        &$originalValue
    ) {
        /** @var bool $isDateTimeEqualToValueFromUnitOfWorkButNotSame */
        $isDateTimeEqualToValueFromUnitOfWorkButNotSame = false;

        /** @var string $fieldName */
        $fieldName = $this->columnToFieldName($column);

        /** @var array<string, mixed> $originalEntityData */
        $originalEntityData = $unitOfWork->getOriginalEntityData($entity);

        if (isset($originalEntityData[$fieldName])) {
            /** @var mixed $originalValue */
            $originalValue = $originalEntityData[$fieldName];

            if (is_object($originalValue) && get_class($originalValue) === get_class($value)) {
                /** @var DateTime $originalDateTime */
                $originalDateTime = $originalValue;

                if ($originalDateTime !== $value) {
                    /** @var array<string, int|float> $diff */
                    $diff = (array)$value->diff($originalDateTime);

                    if (!empty($diff) && 0 === (int)array_sum($diff) && 0 === (int)max($diff)) {
                        $isDateTimeEqualToValueFromUnitOfWorkButNotSame = true;
                    }
                }
            }
        }

        return $isDateTimeEqualToValueFromUnitOfWorkButNotSame;
    }

}
