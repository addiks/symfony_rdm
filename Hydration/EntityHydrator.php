<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Hydration;

use ReflectionClass;
use ReflectionProperty;
use Doctrine\Common\Util\ClassUtils;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\EntityHydratorInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Addiks\RDMBundle\Hydration\HydrationContext;
use Webmozart\Assert\Assert;
use Exception;
use Throwable;
use Doctrine\ORM\Mapping\MappingException;

final class EntityHydrator implements EntityHydratorInterface
{

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var DataLoaderInterface
     */
    private $dbalDataLoader;

    public function __construct(
        MappingDriverInterface $mappingDriver,
        DataLoaderInterface $dbalDataLoader
    ) {
        $this->mappingDriver = $mappingDriver;
        $this->dbalDataLoader = $dbalDataLoader;
    }

    public function hydrateEntity($entity, EntityManagerInterface $entityManager): void
    {
        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = $this->dbalDataLoader->loadDBALDataForEntity(
            $entity,
            $entityManager
        );

        /** @var string $className */
        $className = get_class($entity);

        do {
            if (class_exists(ClassUtils::class)) {
                $className = ClassUtils::getRealClass($className);
            }

            $classReflection = new ReflectionClass($className);

            /** @var ?EntityMappingInterface $mapping */
            $mapping = $this->mappingDriver->loadRDMMetadataForClass($className);

            if ($mapping instanceof EntityMappingInterface) {
                /** @var string $processDescription */
                $processDescription = sprintf("of entity '%s'", $className);

                try {
                    if ($mapping instanceof EntityMappingInterface) {
                        $context = new HydrationContext($entity, $entityManager);

                        foreach ($mapping->getFieldMappings() as $fieldName => $fieldMapping) {
                            /** @var MappingInterface $fieldMapping */

                            $processDescription = sprintf(
                                "of field '%s' of entity '%s'",
                                $fieldName,
                                $className
                            );

                            /** @var mixed $value */
                            $value = $fieldMapping->resolveValue(
                                $context,
                                $dataFromAdditionalColumns
                            );

                            /** @var ReflectionClass $concreteClassReflection */
                            $concreteClassReflection = $classReflection;

                            while (!$concreteClassReflection->hasProperty($fieldName)) {
                                $concreteClassReflection = $concreteClassReflection->getParentClass();

                                Assert::object($concreteClassReflection, sprintf(
                                    "Property '%s' does not exist on object of class '%s'!",
                                    $fieldName,
                                    $className
                                ));
                            }

                            /** @var ReflectionProperty $propertyReflection */
                            $propertyReflection = $concreteClassReflection->getProperty($fieldName);

                            $propertyReflection->setAccessible(true);
                            $propertyReflection->setValue($entity, $value);
                        }
                    }

                } catch (Throwable $exception) {
                    throw new MappingException(sprintf(
                        "Exception during hydration %s!",
                        $processDescription
                    ), 0, $exception);
                }
            }

            $className = current(class_parents($className));
        } while (class_exists($className));
    }

    public function assertHydrationOnEntity($entity, EntityManagerInterface $entityManager): void
    {
        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = $this->dbalDataLoader->loadDBALDataForEntity(
            $entity,
            $entityManager
        );

        /** @var string $className */
        $className = get_class($entity);

        do {
            if (class_exists(ClassUtils::class)) {
                $className = ClassUtils::getRealClass($className);
            }

            $classReflection = new ReflectionClass($className);

            /** @var ?EntityMappingInterface $mapping */
            $mapping = $this->mappingDriver->loadRDMMetadataForClass($className);

            if ($mapping instanceof EntityMappingInterface) {
                if ($mapping instanceof EntityMappingInterface) {
                    $context = new HydrationContext($entity, $entityManager);

                    foreach ($mapping->getFieldMappings() as $fieldName => $fieldMapping) {
                        /** @var MappingInterface $fieldMapping */

                        /** @var ReflectionClass $concreteClassReflection */
                        $concreteClassReflection = $classReflection;

                        while (!$concreteClassReflection->hasProperty($fieldName)) {
                            $concreteClassReflection = $concreteClassReflection->getParentClass();

                            Assert::notNull($concreteClassReflection, sprintf(
                                "Property '%s' does not exist on object of class '%s'!",
                                $fieldName,
                                $className
                            ));
                        }

                        /** @var ReflectionProperty $propertyReflection */
                        $propertyReflection = $concreteClassReflection->getProperty($fieldName);

                        $propertyReflection->setAccessible(true);

                        /** @var object $actualValue */
                        $actualValue = $propertyReflection->getValue($entity);

                        $fieldMapping->assertValue(
                            $context,
                            $dataFromAdditionalColumns,
                            $actualValue
                        );
                    }
                }
            }

            $className = current(class_parents($className));
        } while (class_exists($className));
    }

}
