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
        $entity,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $object */
        $object = null;

        if ($objectMapping instanceof ObjectMappingInterface) {
            /** @var string $className */
            $className = $objectMapping->getClassName();

            /** @var null|CallDefinitionInterface $factory */
            $factory = $objectMapping->getFactory();

            $reflectionClass = new ReflectionClass($className);

            if ($factory instanceof CallDefinitionInterface) {
                $object = $this->callDefinitionExecuter->executeCallDefinition(
                    $factory,
                    $entity,
                    $dataFromAdditionalColumns
                );

            } else {
                /** @var object $object */
                $object = $reflectionClass->newInstanceWithoutConstructor();
            }

            foreach ($objectMapping->getFieldMappings() as $fieldName => $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                /** @var mixed $fieldValue */
                $fieldValue = $this->fieldValueResolver->resolveValue(
                    $fieldMapping,
                    $entity,
                    $dataFromAdditionalColumns
                );

                /** @var ReflectionProperty $reflectionProperty */
                $reflectionProperty = $reflectionClass->getProperty($fieldName);

                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($object, $fieldValue);
            }

        }

        return $object;
    }

    public function revertValue(
        MappingInterface $objectMapping,
        $entity,
        $valueFromEntityField
    ): array {
        /** @var array<scalar> $data */
        $data = array();

        if ($objectMapping instanceof ObjectMappingInterface) {
            /** @var string $className */
            $className = $objectMapping->getClassName();

            $reflectionClass = new ReflectionClass($className);

            foreach ($objectMapping->getFieldMappings() as $fieldName => $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                /** @var ReflectionProperty $reflectionProperty */
                $reflectionProperty = $reflectionClass->getProperty($fieldName);

                $reflectionProperty->setAccessible(true);
                $valueFromField = null;

                if (is_object($valueFromEntityField)) {
                    $valueFromField = $reflectionProperty->getValue($valueFromEntityField);
                }

                $data = array_merge($data, $this->fieldValueResolver->revertValue(
                    $fieldMapping,
                    $entity,
                    $valueFromField
                ));
            }
        }

        return $data;
    }

    public function assertValue(
        MappingInterface $objectMapping,
        $entity,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        if ($objectMapping instanceof ObjectMappingInterface) {
            /** @var string $className */
            $className = $objectMapping->getClassName();

            if (!is_null($actualValue) && !$actualValue instanceof $className) {
                throw FailedRDMAssertionException::expectedInstanceOf(
                    $objectMapping->getClassName(),
                    get_class($entity),
                    $objectMapping->describeOrigin()
                );
            }
        }
    }

}
