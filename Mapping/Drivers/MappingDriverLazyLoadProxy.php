<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Drivers;

use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Exception\InvalidMappingException;

final class MappingDriverLazyLoadProxy implements MappingDriverInterface
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
     * @var ?MappingDriverInterface
     */
    private $loadedMetadataDriver;

    public function __construct(ContainerInterface $container, string $serviceId)
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        return $this->loadMetadataDriver()->loadRDMMetadataForClass($className);
    }

    private function loadMetadataDriver(): MappingDriverInterface
    {
        if (is_null($this->loadedMetadataDriver)) {
            /** @var object $loadMetadataDriver */
            $loadedMetadataDriver = $this->container->get($this->serviceId);

            if ($loadedMetadataDriver instanceof MappingDriverInterface) {
                $this->loadedMetadataDriver = $loadedMetadataDriver;

            } else {
                throw new InvalidMappingException(sprintf(
                    "Service with id '%s' was expected to be of type %s but was not!",
                    $this->serviceId,
                    MappingDriverInterface::class
                ));
            }
        }

        return $this->loadedMetadataDriver;
    }

}
