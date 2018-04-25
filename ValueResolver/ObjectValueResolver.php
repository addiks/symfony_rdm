<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\ValueResolver;

use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\ObjectMappingInterface;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use ReflectionProperty;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Addiks\RDMBundle\ValueResolver\CallDefinitionExecuterInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

final class ObjectValueResolver implements ValueResolverInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ValueResolverInterface
     */
    private $fieldValueResolver;

    /**
     * @var CallDefinitionExecuterInterface
     */
    private $callDefinitionExecuter;

    public function __construct(
        ContainerInterface $container,
        ValueResolverInterface $fieldValueResolver,
        CallDefinitionExecuterInterface $callDefinitionExecuter
    ) {
        $this->container = $container;
        $this->fieldValueResolver = $fieldValueResolver;
        $this->callDefinitionExecuter = $callDefinitionExecuter;
    }

    public function resolveValue(
        MappingInterface $objectMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $object */
        $object = null;

        if ($objectMapping instanceof ObjectMappingInterface) {
            /** @var string $className */
            $className = $objectMapping->getClassName();

            /** @var null|CallDefinitionInterface $factory */
            $factory = $objectMapping->getFactory();

            /** @var string|null $id */
            $id = $objectMapping->getId();

            /** @var string|null $referencedId */
            $referencedId = $objectMapping->getReferencedId();

            $reflectionClass = new ReflectionClass($className);

            /** @var object|string $object */
            $object = $className;

            if ($reflectionClass->isInstantiable()) {
                $object = $reflectionClass->newInstanceWithoutConstructor();
            }

            $context->pushOnObjectHydrationStack($object);

            if (!empty($referencedId)) {
                $object = $context->getRegisteredValue($referencedId);

            } elseif ($factory instanceof CallDefinitionInterface) {
                /** @var array<string, string> $factoryData */
                $factoryData = $dataFromAdditionalColumns;

                /** @var Column|null $column */
                $column = $objectMapping->getDBALColumn();

                if ($column instanceof Column && !array_key_exists("", $factoryData)) {
                    /** @var Type $type */
                    $type = $column->getType();

                    /** @var string $columnName */
                    $columnName = $column->getName();

                    if (isset($dataFromAdditionalColumns[$columnName])) {
                        /** @var Connection $connection */
                        $connection = $context->getEntityManager()->getConnection();

                        $dataFromAdditionalColumns[$columnName] = $type->convertToPHPValue(
                            $dataFromAdditionalColumns[$columnName],
                            $connection->getDatabasePlatform()
                        );

                        $factoryData[""] = $dataFromAdditionalColumns[$columnName];
                    }
                }

                $object = $this->callDefinitionExecuter->executeCallDefinition(
                    $factory,
                    $context,
                    $factoryData
                );
            }

            // $object may have been replaced during creation, re-assign on top of stack.
            $context->popFromObjectHydrationStack();
            $context->pushOnObjectHydrationStack($object);

            if (!empty($id) && !$context->hasRegisteredValue($id)) {
                $context->registerValue($id, $object);
            }

            foreach ($objectMapping->getFieldMappings() as $fieldName => $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                /** @var mixed $fieldValue */
                $fieldValue = $this->fieldValueResolver->resolveValue(
                    $fieldMapping,
                    $context,
                    $dataFromAdditionalColumns
                );

                /** @var ReflectionProperty $reflectionProperty */
                $reflectionProperty = $reflectionClass->getProperty($fieldName);

                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($object, $fieldValue);
            }

            $context->popFromObjectHydrationStack();
        }

        return $object;
    }

    public function revertValue(
        MappingInterface $objectMapping,
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<scalar> $data */
        $data = array();

        if ($objectMapping instanceof ObjectMappingInterface) {
            /** @var string $className */
            $className = $objectMapping->getClassName();

            $reflectionClass = new ReflectionClass($className);

            $context->pushOnObjectHydrationStack($valueFromEntityField);

            foreach ($objectMapping->getFieldMappings() as $fieldName => $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                /** @var ReflectionProperty $reflectionProperty */
                $reflectionProperty = $reflectionClass->getProperty($fieldName);

                $reflectionProperty->setAccessible(true);
                $valueFromField = null;

                if (is_object($valueFromEntityField)) {
                    $valueFromField = $reflectionProperty->getValue($valueFromEntityField);
                }

                /** @var array<string, mixed> $fieldData */
                $fieldData = $this->fieldValueResolver->revertValue(
                    $fieldMapping,
                    $context,
                    $valueFromField
                );

                if (array_key_exists("", $fieldData)) {
                    $fieldData[$fieldName] = $fieldData[""];
                    unset($fieldData[""]);
                }

                $data = array_merge($data, $fieldData);
            }

            /** @var null|CallDefinitionInterface $serializerMapping */
            $serializerMapping = $objectMapping->getSerializer();

            if ($serializerMapping instanceof CallDefinitionInterface) {
                /** @var string $columnName */
                $columnName = '';

                /** @var Column|null $column */
                $column = $objectMapping->getDBALColumn();

                if ($column instanceof Column) {
                    $columnName = $column->getName();
                }

                $data[$columnName] = $this->callDefinitionExecuter->executeCallDefinition(
                    $serializerMapping,
                    $context,
                    $data
                );

                if ($column instanceof Column) {
                    /** @var Type $type */
                    $type = $column->getType();

                    /** @var Connection $connection */
                    $connection = $context->getEntityManager()->getConnection();

                    $data[$columnName] = $type->convertToDatabaseValue(
                        $data[$columnName],
                        $connection->getDatabasePlatform()
                    );
                }
            }

            $context->popFromObjectHydrationStack();
        }

        return $data;
    }

    public function assertValue(
        MappingInterface $objectMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        if ($objectMapping instanceof ObjectMappingInterface) {
            /** @var string $className */
            $className = $objectMapping->getClassName();

            if (!is_null($actualValue) && !$actualValue instanceof $className) {
                throw FailedRDMAssertionException::expectedInstanceOf(
                    $objectMapping->getClassName(),
                    $context->getEntityClass(),
                    $objectMapping->describeOrigin()
                );
            }
        }
    }

}
