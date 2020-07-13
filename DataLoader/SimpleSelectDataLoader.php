<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\DataLoader;

use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use ReflectionProperty;
use Doctrine\ORM\Query\Expr;
use Doctrine\DBAL\Driver\Statement;
use PDO;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContext;
use Webmozart\Assert\Assert;
use ErrorException;

/**
 * A very simple loader that just executes one simple select statement for every entity to load the data for.
 *
 * Because it executes one query for every entity to load data for, this could (and probably will) have an bad impact on
 * performance.
 *
 * TODO: This may be replaced in the future by integrating that data-loading into the select(s) executed by doctrine.
 */
final class SimpleSelectDataLoader implements DataLoaderInterface
{

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var array<array<scalar>>
     */
    private $originalData = array();

    /**
     * @var int
     */
    private $originalDataLimit;

    public function __construct(
        MappingDriverInterface $mappingDriver,
        int $originalDataLimit = 1000
    ) {
        $this->mappingDriver = $mappingDriver;
        $this->originalDataLimit = $originalDataLimit;
    }

    /**
     * @param object $entity
     */
    public function loadDBALDataForEntity($entity, EntityManagerInterface $entityManager): array
    {
        /** @var string $className */
        $className = get_class($entity);

        /** @var string $entityObjectHash */
        $entityObjectHash = spl_object_hash($entity);

        $this->originalData[$entityObjectHash] = [];

        /** @var array<string> $additionalData */
        $additionalData = array();

        do {
            if (class_exists(ClassUtils::class)) {
                $className = ClassUtils::getRealClass($className);
            }

            if (!$entityManager->getMetadataFactory()->isTransient($className)) {
                /** @var ClassMetadata $classMetaData */
                $classMetaData = $entityManager->getClassMetadata($className);

                /** @var ?EntityMappingInterface $entityMapping */
                $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($className);

                if ($entityMapping instanceof EntityMappingInterface) {
                    /** @var array<Column> $additionalColumns */
                    $additionalColumns = $entityMapping->collectDBALColumns();

                    if (!empty($additionalColumns)) {
                        /** @var Connection $connection */
                        $connection = $entityManager->getConnection();

                        /** @var QueryBuilder $queryBuilder */
                        $queryBuilder = $connection->createQueryBuilder();

                        /** @var Expr $expr */
                        $expr = $queryBuilder->expr();

                        foreach ($additionalColumns as $column) {
                            /** @var Column $column */

                            $queryBuilder->addSelect($column->getName());
                        }

                        $reflectionClass = new ReflectionClass($className);

                        /** @var bool $hasId */
                        $hasId = false;

                        foreach ($classMetaData->identifier as $idFieldName) {
                            /** @var string $idFieldName */

                            /** @var array $idColumn */
                            $idColumn = $classMetaData->fieldMappings[$idFieldName];

                            /** @var ReflectionProperty $reflectionProperty */
                            $reflectionProperty = $reflectionClass->getProperty($idFieldName);

                            $reflectionProperty->setAccessible(true);

                            $idValue = $reflectionProperty->getValue($entity);

                            if (!empty($idValue)) {
                                $hasId = true;
                                if (!is_numeric($idValue) || empty($idValue)) {
                                    $idValue = "'{$idValue}'";
                                }
                                $queryBuilder->andWhere($expr->eq($idColumn['columnName'], $idValue));
                            }
                        }

                        if ($hasId) {
                            $queryBuilder->from($classMetaData->getTableName());
                            $queryBuilder->setMaxResults(1);

                            /** @var Statement $statement */
                            $statement = $queryBuilder->execute();

                            $additionalData = $statement->fetch(PDO::FETCH_ASSOC);

                            if (!is_array($additionalData)) {
                                $additionalData = array();
                            }

                            $this->originalData[$entityObjectHash] = array_merge(
                                $this->originalData[$entityObjectHash],
                                $additionalData
                            );

                            if (count($this->originalData) > $this->originalDataLimit) {
                                array_shift($this->originalData);
                            }
                        }
                    }
                }
            }

            $className = current(class_parents($className));
        } while (class_exists($className));

        return $this->originalData[$entityObjectHash] ?? [];
    }

    /**
     * @param object $entity
     */
    public function storeDBALDataForEntity($entity, EntityManagerInterface $entityManager): void
    {
        /** @var string $className */
        $className = get_class($entity);

        if (class_exists(ClassUtils::class)) {
            $className = ClassUtils::getRealClass($className);
        }

        do {
            /** @var null|EntityMappingInterface $entityMapping */
            $entityMapping = $this->mappingDriver->loadRDMMetadataForClass($className);

            if ($entityMapping instanceof EntityMappingInterface) {
                /** @var array<scalar> */
                $additionalData = $this->collectAdditionalDataForEntity($entity, $entityMapping, $entityManager);

                if ($this->hasDataChanged($entity, $additionalData)) {
                    /** @var ClassMetadata $classMetaData */
                    $classMetaData = $entityManager->getClassMetadata($className);

                    /** @var array<scalar> $identifier */
                    $identifier = $this->collectIdentifierForEntity($entity, $entityMapping, $classMetaData);

                    /** @var string $tableName */
                    $tableName = $classMetaData->getTableName();

                    /** @var Connection $connection */
                    $connection = $entityManager->getConnection();

                    $connection->update($tableName, $additionalData, $identifier);
                }
            }

            $className = current(class_parents($className));
        } while (class_exists($className));
    }

    /**
     * @param object $entity
     */
    public function removeDBALDataForEntity($entity, EntityManagerInterface $entityManager): void
    {
        # This data-loader does not store data outside the entity-table.
        # No additional data need to be removed.
    }

    public function prepareOnMetadataLoad(EntityManagerInterface $entityManager, ClassMetadata $classMetadata): void
    {
        # This data-loader does not need any preperation
    }

    /**
     * @param object $entity
     */
    private function hasDataChanged($entity, array $additionalData): bool
    {
        /** @var array<scalar> */
        $originalData = array();

        /** @var string $entityObjectHash */
        $entityObjectHash = spl_object_hash($entity);

        if (isset($this->originalData[$entityObjectHash])) {
            $originalData = $this->originalData[$entityObjectHash];
        }

        /** @var bool $hasDataChanged */
        $hasDataChanged = false;

        foreach ($additionalData as $key => $value) {
            if (!array_key_exists($key, $originalData) || $originalData[$key] != $value) {
                $hasDataChanged = true;
            }
        }

        return $hasDataChanged;
    }

    /**
     * @param object $entity
     */
    private function collectAdditionalDataForEntity(
        $entity,
        EntityMappingInterface $entityMapping,
        EntityManagerInterface $entityManager#,
        #string $className = null
    ): array {
        /** @var array<scalar> */
        $additionalData = array();

        $reflectionClass = new ReflectionClass($entityMapping->getEntityClassName());

        $context = new HydrationContext($entity, $entityManager);

        foreach ($entityMapping->getFieldMappings() as $fieldName => $entityFieldMapping) {
            /** @var MappingInterface $entityFieldMapping */

            /** @var ReflectionClass $concreteReflectionClass */
            $concreteReflectionClass = $reflectionClass;

            while (is_object($concreteReflectionClass) && !$concreteReflectionClass->hasProperty($fieldName)) {
                $concreteReflectionClass = $concreteReflectionClass->getParentClass();
            }

            if (!is_object($concreteReflectionClass)) {
                throw new ErrorException(sprintf(
                    "Property '%s' does not exist on object of class '%s'!",
                    $fieldName,
                    $entityMapping->getEntityClassName()
                ));
            }

            /** @var ReflectionProperty $reflectionProperty */
            $reflectionProperty = $concreteReflectionClass->getProperty($fieldName);

            $reflectionProperty->setAccessible(true);

            /** @var mixed $valueFromEntityField */
            $valueFromEntityField = $reflectionProperty->getValue($entity);

            /** @var array<scalar> $fieldAdditionalData */
            $fieldAdditionalData = $entityFieldMapping->revertValue(
                $context,
                $valueFromEntityField
            );

            $additionalData = array_merge($additionalData, $fieldAdditionalData);
        }

        return $additionalData;
    }

    /**
     * @param object $entity
     */
    private function collectIdentifierForEntity(
        $entity,
        EntityMappingInterface $entityMapping,
        ClassMetadata $classMetaData
    ): array {
        $reflectionClass = new ReflectionClass($entityMapping->getEntityClassName());

        /** @var array<scalar> $identifier */
        $identifier = array();

        foreach ($classMetaData->identifier as $idFieldName) {
            /** @var string $idFieldName */

            /** @var array $idColumn */
            $idColumn = $classMetaData->fieldMappings[$idFieldName];

            /** @var ReflectionProperty $reflectionProperty */
            $reflectionProperty = $reflectionClass->getProperty($idFieldName);

            $reflectionProperty->setAccessible(true);

            $idValue = $reflectionProperty->getValue($entity);

            $identifier[$idColumn['columnName']] = $idValue;
        }

        return $identifier;
    }

}
