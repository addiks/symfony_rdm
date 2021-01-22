<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping;

use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Addiks\RDMBundle\Hydration\HydrationContext;
use ErrorException;
use ReflectionProperty;
use ReflectionObject;

final class EntityMapping implements EntityMappingInterface
{

    /**
     * @var class-string
     */
    private $className;

    /**
     * @var array<MappingInterface>
     */
    private $fieldMappings = array();

    /** @var array<Column>|null */
    private $dbalColumnsCache;

    /** @param class-string $className */
    public function __construct(string $className, array $fieldMappings)
    {
        $this->className = $className;

        foreach ($fieldMappings as $fieldName => $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            Assert::isInstanceOf($fieldMapping, MappingInterface::class);

            $this->fieldMappings[$fieldName] = $fieldMapping;
        }
    }

    public function getEntityClassName(): string
    {
        return $this->className;
    }

    public function getDBALColumn(): ?Column
    {
        return null;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    public function describeOrigin(): string
    {
        return $this->className;
    }

    public function collectDBALColumns(): array
    {
        if (is_null($this->dbalColumnsCache)) {
            /** @var array<Column> $additionalColumns */
            $additionalColumns = array();

            foreach ($this->fieldMappings as $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                $additionalColumns = array_merge(
                    $additionalColumns,
                    $fieldMapping->collectDBALColumns()
                );
            }

            $this->dbalColumnsCache = $additionalColumns;
        }

        return $this->dbalColumnsCache;
    }

    public function getFactory(): ?CallDefinitionInterface
    {
        return null;
    }

    public function getSerializer(): ?CallDefinitionInterface
    {
        return null;
    }

    public function getId(): ?string
    {
        return null;
    }

    public function getReferencedId(): ?string
    {
        return null;
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var array<string, mixed> $entityData */
        $entityData = array();

        /** @var MappingInterface $fieldMapping */
        foreach ($this->fieldMappings as $fieldName => $fieldMapping) {
            $entityData[$fieldName] = $fieldMapping->resolveValue($context, $dataFromAdditionalColumns);
        }

        return $entityData;
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<string, mixed> $additionalData */
        $additionalData = array();

        /** @var object|null $entity */
        $entity = $valueFromEntityField;

        if (is_object($entity)) {
            $reflectionObject = new ReflectionObject($entity);

            $reflectionClass = new ReflectionClass($this->className);

            /** @var MappingInterface $fieldMapping */
            foreach ($this->fieldMappings as $fieldName => $fieldMapping) {

                /** @var ReflectionClass $concreteReflectionClass */
                $concreteReflectionClass = $reflectionClass;

                while (is_object($concreteReflectionClass) && !$concreteReflectionClass->hasProperty($fieldName)) {
                    $concreteReflectionClass = $concreteReflectionClass->getParentClass();
                }

                if (!is_object($concreteReflectionClass)) {
                    throw new ErrorException(sprintf(
                        "Property '%s' does not exist on object of class '%s'!",
                        $fieldName,
                        $this->className
                    ));
                }

                /** @var ReflectionProperty $reflectionProperty */
                $reflectionProperty = $concreteReflectionClass->getProperty($fieldName);
                $reflectionProperty->setAccessible(true);

                /** @var mixed $valueFromEntityField */
                $valueFromEntityField = $reflectionProperty->getValue($entity);

                $additionalData = array_merge(
                    $additionalData,
                    $fieldMapping->revertValue($context, $valueFromEntityField)
                );
            }
        }

        return $additionalData;
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
        foreach ($this->fieldMappings as $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            $fieldMapping->wakeUpMapping($container);
        }
    }

}
