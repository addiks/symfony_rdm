<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Hydration;

use ReflectionClass;
use ReflectionProperty;
use ErrorException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Util\ClassUtils;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Hydration\EntityHydratorInterface;

final class EntityHydrator implements EntityHydratorInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    public function __construct(
        ContainerInterface $container,
        MappingDriverInterface $mappingDriver
    ) {
        $this->mappingDriver = $mappingDriver;
        $this->container = $container;
    }

    public function hydrateEntity($entity)
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

        if ($mapping instanceof EntityMappingInterface) {
            foreach ($mapping->getFieldMappings() as $fieldName => $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                /** @var ReflectionProperty $propertyReflection */
                $propertyReflection = $classReflection->getProperty($fieldName);

                /** @var mixed $value */
                $value = null;

                if ($fieldMapping instanceof ServiceMapping) {
                    $value = $this->loadService($fieldMapping->getServiceId(), $propertyReflection);
                }

                $propertyReflection->setAccessible(true);
                $propertyReflection->setValue($entity, $value);
                $propertyReflection->setAccessible(false);
            }
        }
    }

    public function assertHydrationOnEntity($entity)
    {
        /** @var string $className */
        $className = get_class($entity);

        $classReflection = new ReflectionClass($className);

        /** @var ?EntityMappingInterface $mapping */
        $mapping = $this->mappingDriver->loadRDMMetadataForClass($className);

        if ($mapping instanceof EntityMappingInterface) {
            foreach ($mapping->getFieldMappings() as $fieldName => $fieldMapping) {
                /** @var MappingInterface $fieldMapping */

                /** @var ReflectionProperty $propertyReflection */
                $propertyReflection = $classReflection->getProperty($fieldName);

                if ($fieldMapping instanceof ServiceMapping && !$fieldMapping->isLax()) {
                    /** @var string $serviceId */
                    $serviceId = $fieldMapping->getServiceId();

                    /** @var object $expectedService */
                    $expectedService = $this->loadService($serviceId, $propertyReflection);

                    $propertyReflection->setAccessible(true);

                    /** @var object $actualService */
                    $actualService = $propertyReflection->getValue($entity);

                    $propertyReflection->setAccessible(false);

                    $this->assertCorrectService($expectedService, $actualService, $serviceId, $propertyReflection);
                }
            }
        }
    }

    private function loadService(string $serviceId, ReflectionProperty $propertyReflection)
    {
        if (!$this->container->has($serviceId)) {
            /** @var ReflectionClass $classReflection */
            $classReflection = $propertyReflection->getDeclaringClass();

            throw new ErrorException(sprintf(
                "Referenced non-existent service '%s' in field '%s' of entity '%s'!",
                $serviceId,
                $propertyReflection->getName(),
                $classReflection->getName()
            ));
        }

        /** @var object $service */
        $service = $this->container->get($serviceId);

        return $service;
    }

    private function assertCorrectService(
        $expectedService,
        $actualService,
        string $serviceId,
        ReflectionProperty $propertyReflection
    ) {
        if ($expectedService !== $actualService) {
            /** @var string $actualDescription */
            $actualDescription = null;

            if (is_object($actualService)) {
                $actualDescription = get_class($actualService) . '#' . spl_object_hash($actualService);

            } else {
                $actualDescription = gettype($actualService);
            }

            /** @var string $expectedDescription */
            $expectedDescription = null;

            if (is_object($expectedService)) {
                $expectedDescription = get_class($expectedService) . '#' . spl_object_hash($expectedService);

            } else {
                $expectedDescription = gettype($expectedService);
            }

            /** @var ReflectionClass $classReflection */
            $classReflection = $propertyReflection->getDeclaringClass();

            throw new ErrorException(sprintf(
                "Expected service %s (%s) to be in field %s of entity %s, was %s instead!",
                $serviceId,
                $expectedDescription,
                $propertyReflection->getName(),
                $classReflection->getName(),
                $actualDescription
            ));
        }
    }

}
