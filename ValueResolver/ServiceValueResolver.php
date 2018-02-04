<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\ValueResolver;

use ErrorException;
use ReflectionClass;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Addiks\RDMBundle\Mapping\ServiceMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ServiceValueResolver implements ValueResolverInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function resolveValue(
        MappingInterface $fieldMapping,
        $entity,
        array $databaseData
    ) {
        /** @var object $service */
        $service = null;

        if ($fieldMapping instanceof ServiceMappingInterface) {
            /** @var string $serviceId */
            $serviceId = $fieldMapping->getServiceId();

            if (!$this->container->has($serviceId)) {
                throw new ErrorException(sprintf(
                    "Referenced non-existent service '%s' %s!",
                    $serviceId,
                    $fieldMapping->describeOrigin()
                ));
            }

            /** @var object $service */
            $service = $this->container->get($serviceId);
        }

        return $service;
    }

    public function revertValue(
        MappingInterface $fieldMapping,
        $entity,
        $valueFromEntityField
    ): array {
        return []; # Nothing to revert to for static services
    }

    public function assertValue(
        MappingInterface $fieldMapping,
        $entity,
        array $databaseData,
        $actualService
    ) {
        if ($fieldMapping instanceof ServiceMappingInterface && !$fieldMapping->isLax()) {
            /** @var object $expectedService */
            $expectedService = $this->resolveValue($fieldMapping, $entity, $databaseData);

            /** @var string $serviceId */
            $serviceId = $fieldMapping->getServiceId();

            if ($expectedService !== $actualService) {
                throw FailedRDMAssertionException::expectedDifferentService(
                    $serviceId,
                    new ReflectionClass(get_class($entity)),
                    $expectedService,
                    $actualService
                );
            }
        }
    }

}
