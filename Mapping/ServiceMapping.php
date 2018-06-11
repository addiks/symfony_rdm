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

use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use ErrorException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ReflectionClass;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class ServiceMapping implements MappingInterface
{

    /**
     * The service-id of the service to load for given entitiy-field.
     *
     * @var string
     */
    private $serviceId;

    /**
     * Set this to true if this field should not be checked for the correct service on persist.
     * This check is a safety-net and you should know what you are doing when you are disabling it.
     * You have been warned.
     *
     * @var bool
     */
    private $lax = false;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container,
        string $serviceId,
        bool $lax = false,
        string $origin = "unknown"
    ) {
        $this->container = $container;
        $this->serviceId = $serviceId;
        $this->lax = $lax;
        $this->origin = $origin;
    }

    public function __sleep(): array
    {
        return [
            'serviceId',
            'lax',
            'origin',
        ];
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function isLax(): bool
    {
        return $this->lax;
    }

    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        return [];
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var object $service */
        $service = null;

        if (!$this->container->has($this->serviceId)) {
            throw new ErrorException(sprintf(
                "Referenced non-existent service '%s' %s!",
                $this->serviceId,
                $this->origin
            ));
        }

        /** @var object $service */
        $service = $this->container->get($this->serviceId);

        return $service;
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        return []; # Nothing to revert to for static services
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualService
    ): void {
        if (!$this->lax) {
            /** @var object $expectedService */
            $expectedService = $this->resolveValue($context, $dataFromAdditionalColumns);

            if ($expectedService !== $actualService) {
                throw FailedRDMAssertionException::expectedDifferentService(
                    $this->serviceId,
                    new ReflectionClass($context->getEntityClass()),
                    $expectedService,
                    $actualService
                );
            }
        }
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
        $this->container = $container;
    }

}
