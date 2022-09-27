<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\DriverFactories;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverChain as RDMMappingDriverChain;

final class MappingDriverChainFactory implements MappingDriverFactoryInterface
{

    /**
     * @var MappingDriverFactoryInterface
     */
    private $rootMetadataDriverFactory;

    public function __construct(MappingDriverFactoryInterface $rootMetadataDriverFactory)
    {
        $this->rootMetadataDriverFactory = $rootMetadataDriverFactory;
    }

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        /** @var ?MappingDriverInterface $rdmMetadataDriver */
        $rdmMetadataDriver = null;

        if ($mappingDriver instanceof MappingDriverChain) {
            /** @var MappingDriverChain $mappingDriverChain */
            $mappingDriverChain = $mappingDriver;

            /** @var array<MappingDriverFactoryInterface> $subRDMMetadataDrivers */
            $subRDMMetadataDrivers = array();

            foreach ($mappingDriverChain->getDrivers() as $subMappingDriver) {
                /** @var MappingDriver $subMappingDriver */

                $subRDMMetadataDriver = $this->rootMetadataDriverFactory->createRDMMappingDriver(
                    $subMappingDriver
                );

                if ($subRDMMetadataDriver instanceof MappingDriverInterface) {
                    $subRDMMetadataDrivers[] = $subRDMMetadataDriver;
                }
            }

            $rdmMetadataDriver = new RDMMappingDriverChain($subRDMMetadataDrivers);
        }

        return $rdmMetadataDriver;
    }

}
