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

namespace Addiks\RDMBundle\Mapping;

use Addiks\RDMBundle\Mapping\ObjectMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use ReflectionClass;
use ReflectionProperty;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;

final class ObjectMapping implements ObjectMappingInterface
{

    /**
     * @var string
     */
    private $className;

    /**
     * @var array<MappingInterface>
     */
    private $fieldMappings = array();

    /**
     * @var Column|null
     */
    private $column;

    /**
     * @var CallDefinitionInterface|null
     */
    private $factory;

    /**
     * @var CallDefinitionInterface|null
     */
    private $serializer;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $referencedId;

    public function __construct(
        string $className,
        array $fieldMappings,
        Column $column = null,
        string $origin = "undefined",
        CallDefinitionInterface $factory = null,
        CallDefinitionInterface $serializer = null,
        string $id = null,
        string $referencedId = null
    ) {
        $this->className = $className;
        $this->factory = $factory;
        $this->column = $column;
        $this->serializer = $serializer;
        $this->origin = $origin;
        $this->id = $id;
        $this->referencedId = $referencedId;

        foreach ($fieldMappings as $fieldName => $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            Assert::isInstanceOf($fieldMapping, MappingInterface::class);
            Assert::false(is_numeric($fieldName));

            $this->fieldMappings[$fieldName] = $fieldMapping;
        }
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getDBALColumn(): ?Column
    {
        return $this->column;
    }

    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        /** @var array<Column> $additionalColumns */
        $additionalColumns = array();

        if ($this->column instanceof Column) {
            $additionalColumns[] = $this->column;
        }

        foreach ($this->fieldMappings as $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            $additionalColumns = array_merge(
                $additionalColumns,
                $fieldMapping->collectDBALColumns()
            );
        }

        return $additionalColumns;
    }

    public function getFactory(): ?CallDefinitionInterface
    {
        return $this->factory;
    }

    public function getSerializer(): ?CallDefinitionInterface
    {
        return $this->serializer;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getReferencedId(): ?string
    {
        return $this->referencedId;
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $object */
        $object = null;

        $reflectionClass = new ReflectionClass($this->className);

        /** @var object|string $object */
        $object = $this->className;

        if ($reflectionClass->isInstantiable()) {
            $object = $reflectionClass->newInstanceWithoutConstructor();
        }

        $context->pushOnObjectHydrationStack($object);

        if (!empty($this->referencedId)) {
            $object = $context->getRegisteredValue($this->referencedId);

        } elseif ($this->factory instanceof CallDefinitionInterface) {
            /** @var array<string, string> $factoryData */
            $factoryData = $dataFromAdditionalColumns;

            if ($this->column instanceof Column && !array_key_exists("", $factoryData)) {
                /** @var Type $type */
                $type = $this->column->getType();

                /** @var string $columnName */
                $columnName = $this->column->getName();

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

            $object = $this->factory->execute(
                $context,
                $factoryData
            );
        }

        // $object may have been replaced during creation, re-assign on top of stack.
        $context->popFromObjectHydrationStack();
        $context->pushOnObjectHydrationStack($object);

        if (!empty($this->id) && !$context->hasRegisteredValue($this->id)) {
            $context->registerValue($this->id, $object);
        }

        foreach ($this->fieldMappings as $fieldName => $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            /** @var mixed $fieldValue */
            $fieldValue = $fieldMapping->resolveValue(
                $context,
                $dataFromAdditionalColumns
            );

            /** @var ReflectionClass $propertyReflectionClass */
            $propertyReflectionClass = $reflectionClass;

            while (is_object($propertyReflectionClass) && !$propertyReflectionClass->hasProperty($fieldName)) {
                $propertyReflectionClass = $propertyReflectionClass->getParentClass();
            }

            if (!is_object($propertyReflectionClass)) {
                throw new ReflectionException(sprintf("Property %s does not exist", $fieldName));
            }

            /** @var ReflectionProperty $reflectionProperty */
            $reflectionProperty = $propertyReflectionClass->getProperty($fieldName);

            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($object, $fieldValue);
        }

        $context->popFromObjectHydrationStack();

        return $object;
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<scalar> $data */
        $data = array();

        $this->assertValue($context, [], $valueFromEntityField);

        $reflectionClass = new ReflectionClass($this->className);

        $context->pushOnObjectHydrationStack($valueFromEntityField);

        foreach ($this->fieldMappings as $fieldName => $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            /** @var ReflectionClass $propertyReflectionClass */
            $propertyReflectionClass = $reflectionClass;

            while (is_object($propertyReflectionClass) && !$propertyReflectionClass->hasProperty($fieldName)) {
                $propertyReflectionClass = $propertyReflectionClass->getParentClass();
            }

            if (!is_object($propertyReflectionClass)) {
                throw new ReflectionException(sprintf(
                    "Property %s does not exist in class %s. (Defined %s)",
                    $fieldName,
                    get_class($valueFromEntityField),
                    $this->origin
                ));
            }

            /** @var ReflectionProperty $reflectionProperty */
            $reflectionProperty = $propertyReflectionClass->getProperty($fieldName);

            $reflectionProperty->setAccessible(true);
            $valueFromField = null;

            if (is_object($valueFromEntityField)) {
                $valueFromField = $reflectionProperty->getValue($valueFromEntityField);
            }

            /** @var array<string, mixed> $fieldData */
            $fieldData = $fieldMapping->revertValue(
                $context,
                $valueFromField
            );

            if (array_key_exists("", $fieldData)) {
                $fieldData[$fieldName] = $fieldData[""];
                unset($fieldData[""]);
            }

            $data = array_merge($data, $fieldData);
        }

        if ($this->serializer instanceof CallDefinitionInterface) {
            /** @var string $columnName */
            $columnName = '';

            if ($this->column instanceof Column) {
                $columnName = $this->column->getName();
            }

            $data[''] = $valueFromEntityField;

            $data[$columnName] = $this->serializer->execute(
                $context,
                $data
            );

            unset($data['']);

            if ($this->column instanceof Column) {
                /** @var Type $type */
                $type = $this->column->getType();

                /** @var Connection $connection */
                $connection = $context->getEntityManager()->getConnection();

                $data[$columnName] = $type->convertToDatabaseValue(
                    $data[$columnName],
                    $connection->getDatabasePlatform()
                );
            }
        }

        $context->popFromObjectHydrationStack();

        return $data;
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        if (!is_null($actualValue) && !$actualValue instanceof $this->className) {
            throw FailedRDMAssertionException::expectedInstanceOf(
                $this->className,
                $context->getEntityClass(),
                $this->origin
            );
        }
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
        if ($this->factory instanceof CallDefinitionInterface) {
            $this->factory->wakeUpCall($container);
        }

        if ($this->serializer instanceof CallDefinitionInterface) {
            $this->serializer->wakeUpCall($container);
        }
    }

}
