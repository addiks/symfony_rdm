<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\DriverFactories;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use ErrorException;

/**
 * Circumvents the symfony "circular reference" error by lazy-loading.
 */
final class MappingDriverFactoryLazyLoadProxy implements MappingDriverFactoryInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var MappingDriverFactoryInterface
     */
    private $actualMappingDriverFactory;

    public function __construct(ContainerInterface $container, string $serviceId)
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        if (is_null($this->actualMappingDriverFactory)) {
            $this->actualMappingDriverFactory = $this->container->get(
                $this->serviceId,
                ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
            );

            if (!$this->actualMappingDriverFactory instanceof MappingDriverFactoryInterface) {
                throw new ErrorException(sprintf(
                    "The service '%s' is not an instance of %s!",
                    $this->serviceId,
                    MappingDriverFactoryInterface::class
                ));
            }
        }

        return $this->actualMappingDriverFactory->createRDMMappingDriver($mappingDriver);
    }

}
