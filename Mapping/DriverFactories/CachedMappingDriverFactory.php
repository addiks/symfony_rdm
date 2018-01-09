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

use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Psr\Cache\CacheItemPoolInterface;
use Addiks\RDMBundle\Mapping\Drivers\CachedMappingDriver;

final class CachedMappingDriverFactory implements MappingDriverFactoryInterface
{

    /**
     * @var MappingDriverFactoryInterface
     */
    private $innerMappingDriverFactory;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    public function __construct(
        MappingDriverFactoryInterface $innerMappingDriverFactory,
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->innerMappingDriverFactory = $innerMappingDriverFactory;
        $this->cacheItemPool = $cacheItemPool;
    }

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        /** @var ?MappingDriverInterface $rdmMappingDriver */
        $rdmMappingDriver = null;

        /** @var MappingDriverInterface $innerRdmMappingDriver */
        $innerRdmMappingDriver = $this->innerMappingDriverFactory->createRDMMappingDriver($mappingDriver);

        if ($innerRdmMappingDriver instanceof MappingDriverInterface) {
            $rdmMappingDriver = new CachedMappingDriver(
                $innerRdmMappingDriver,
                $this->cacheItemPool
            );
        }

        return $rdmMappingDriver;
    }

}
