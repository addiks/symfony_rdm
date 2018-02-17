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
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;

final class EntityHydrator implements EntityHydratorInterface
{

    /**
     * @var ValueResolverInterface
     */
    private $valueResolver;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var DataLoaderInterface
     */
    private $dbalDataLoader;

    public function __construct(
        ValueResolverInterface $valueResolver,
        MappingDriverInterface $mappingDriver,
        DataLoaderInterface $dbalDataLoader
    ) {
        $this->mappingDriver = $mappingDriver;
        $this->valueResolver = $valueResolver;
        $this->dbalDataLoader = $dbalDataLoader;
    }

    public function hydrateEntity($entity, EntityManagerInterface $entityManager): void
    {
        /** @var string $className */
        $className = get_class($entity);

        if (class_exists(ClassUtils::class)) {
            $className = ClassUtils::getRealClass($className);
        }

        /** @var mixed $classReflection */
        $classReflection = new ReflectionClass($className);

        /** @var ?EntityMappingInterface $mapping */
        $mapping = $this->mappingDriver->loadRDMMetadataForClass($className);

        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array();

        if ($mapping instanceof EntityMappingInterface) {
            if (!empty($mapping->collectDBALColumns())) {
                $dataFromAdditionalColumns = $this->dbalDataLoader->loadDBALDataForEntity(
                    $entity,
                    $entityManager
                );
            }

            if ($mapping instanceof EntityMappingInterface) {
                foreach ($mapping->getFieldMappings() as $fieldName => $fieldMapping) {
                    /** @var MappingInterface $fieldMapping */

                    /** @var mixed $value */
                    $value = $this->valueResolver->resolveValue(
                        $fieldMapping,
                        $entity,
                        $dataFromAdditionalColumns
                    );

                    /** @var ReflectionProperty $propertyReflection */
                    $propertyReflection = $classReflection->getProperty($fieldName);

                    $propertyReflection->setAccessible(true);
                    $propertyReflection->setValue($entity, $value);
                    $propertyReflection->setAccessible(false);
                }
            }
        }
    }

    public function assertHydrationOnEntity($entity, EntityManagerInterface $entityManager): void
    {
        /** @var string $className */
        $className = get_class($entity);

        if (class_exists(ClassUtils::class)) {
            $className = ClassUtils::getRealClass($className);
        }

        $classReflection = new ReflectionClass($className);

        /** @var ?EntityMappingInterface $mapping */
        $mapping = $this->mappingDriver->loadRDMMetadataForClass($className);

        /** @var array<string> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array();

        if ($mapping instanceof EntityMappingInterface) {
            if (!empty($mapping->collectDBALColumns())) {
                $dataFromAdditionalColumns = $this->dbalDataLoader->loadDBALDataForEntity(
                    $entity,
                    $entityManager
                );
            }

            if ($mapping instanceof EntityMappingInterface) {
                foreach ($mapping->getFieldMappings() as $fieldName => $fieldMapping) {
                    /** @var MappingInterface $fieldMapping */

                    /** @var ReflectionProperty $propertyReflection */
                    $propertyReflection = $classReflection->getProperty($fieldName);

                    $propertyReflection->setAccessible(true);

                    /** @var object $actualValue */
                    $actualValue = $propertyReflection->getValue($entity);

                    $propertyReflection->setAccessible(false);

                    $this->valueResolver->assertValue(
                        $fieldMapping,
                        $entity,
                        $dataFromAdditionalColumns,
                        $actualValue
                    );
                }
            }
        }
    }

}
