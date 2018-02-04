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

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;

final class MappingDriverFactoryAggregate implements MappingDriverFactoryInterface
{

    /**
     * @var array<MappingDriverFactoryInterface>
     */
    private $innerMappingDriverFactorys = array();

    public function __construct(array $innerMappingDriverFactorys = array())
    {
        foreach ($innerMappingDriverFactorys as $innerMappingDriverFactory) {
            /** @var MappingDriverFactoryInterface $innerMappingDriverFactory */

            $this->addinnerMappingDriverFactory($innerMappingDriverFactory);
        }
    }

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        /** @var ?MappingDriverInterface $rdmMappingDriver */
        $rdmMappingDriver = null;

        foreach ($this->innerMappingDriverFactorys as $innerMappingDriverFactory) {
            /** @var MappingDriverFactoryInterface $innerMappingDriverFactory */

            $rdmMappingDriver = $innerMappingDriverFactory->createRDMMappingDriver($mappingDriver);

            if ($rdmMappingDriver instanceof MappingDriverInterface) {
                break;
            }
        }

        return $rdmMappingDriver;
    }

    private function addinnerMappingDriverFactory(MappingDriverFactoryInterface $innerMappingDriverFactory)
    {
        $this->innerMappingDriverFactorys[] = $innerMappingDriverFactory;
    }

}
