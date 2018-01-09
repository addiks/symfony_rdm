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
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

final class CachedMappingDriver implements MappingDriverInterface
{

    const CACHE_KEY_FORMAT = "addiks_rdm_mapping__%s";

    /**
     * @var MappingDriverInterface
     */
    private $innerMappingDriver;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    public function __construct(
        MappingDriverInterface $innerMappingDriver,
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->innerMappingDriver = $innerMappingDriver;
        $this->cacheItemPool = $cacheItemPool;
    }

    public function loadRDMMetadataForClass($className): array
    {
        /** @var array<Service> $services */
        $services = array();

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cacheItemPool->getItem(sprintf(
            self::CACHE_KEY_FORMAT,
            preg_replace("/[^a-zA-Z0-9]/is", "_", $className)
        ));

        if ($cacheItem->isHit()) {
            $services = unserialize($cacheItem->get());

        } else {
            $services = $this->innerMappingDriver->loadRDMMetadataForClass($className);

            $cacheItem->set(serialize($services));
            $this->cacheItemPool->saveDeferred($cacheItem);
        }

        return $services;
    }

}
