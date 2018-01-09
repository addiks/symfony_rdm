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
use Addiks\RDMBundle\Hydration\EntityServiceHydratorInterface;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Util\ClassUtils;

final class EntityServiceHydrator implements EntityServiceHydratorInterface
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

        /** @var array<Service> $annotations */
        $annotations = $this->mappingDriver->loadRDMMetadataForClass($className);

        foreach ($annotations as $annotation) {
            /** @var Service $annotation */

            if ($annotation instanceof Service) {
                /** @var Service $serviceMapping */
                $serviceMapping = $annotation;

                /** @var ReflectionProperty $propertyReflection */
                $propertyReflection = $classReflection->getProperty($serviceMapping->field);

                /** @var string $serviceId */
                $serviceId = $serviceMapping->id;

                /** @var mixed $service */
                $service = $this->loadService($serviceId, $propertyReflection);

                $propertyReflection->setAccessible(true);
                $propertyReflection->setValue($entity, $service);
                $propertyReflection->setAccessible(false);
            }
        }
    }

    public function assertHydrationOnEntity($entity)
    {
        /** @var string $className */
        $className = get_class($entity);

        $classReflection = new ReflectionClass($className);

        /** @var array<Service> $annotations */
        $annotations = $this->mappingDriver->loadRDMMetadataForClass($className);

        foreach ($annotations as $annotation) {
            /** @var Service $annotation */

            if ($annotation instanceof Service) {
                /** @var Service $serviceMapping */
                $serviceMapping = $annotation;

                /** @var ReflectionProperty $propertyReflection */
                $propertyReflection = $classReflection->getProperty($serviceMapping->field);

                if (!$serviceMapping->lax) {
                    /** @var string $serviceId */
                    $serviceId = $serviceMapping->id;

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
