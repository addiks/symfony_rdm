<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Drivers;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Webmozart\Assert\Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array<string, MappingInterface>
     */
    private $internalCachedMappings = array();

    /**
     * @var int
     */
    private $internalCachedMappingLimit;

    public function __construct(
        MappingDriverInterface $innerMappingDriver,
        ContainerInterface $container,
        CacheItemPoolInterface $cacheItemPool,
        int $internalCachedMappingLimit = 100
    ) {
        $this->innerMappingDriver = $innerMappingDriver;
        $this->container = $container;
        $this->cacheItemPool = $cacheItemPool;
        $this->internalCachedMappingLimit = $internalCachedMappingLimit;
    }

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        if (!array_key_exists($className, $this->internalCachedMappings)) {
            /** @var ?EntityMappingInterface $mapping */
            $mapping = null;

            /** @var CacheItemInterface $cacheItem */
            $cacheItem = $this->cacheItemPool->getItem(sprintf(
                self::CACHE_KEY_FORMAT,
                preg_replace("/[^a-zA-Z0-9]/is", "_", $className)
            ));

            if ($cacheItem->isHit()) {
                $mapping = unserialize($cacheItem->get());

                Assert::isInstanceOf($mapping, MappingInterface::class);

                $mapping->wakeUpMapping($this->container);

            } else {
                $mapping = $this->innerMappingDriver->loadRDMMetadataForClass($className);

                $cacheItem->set(serialize($mapping));
                $this->cacheItemPool->saveDeferred($cacheItem);
            }

            $this->internalCachedMappings[$className] = $mapping;

            if (count($this->internalCachedMappings) > $this->internalCachedMappingLimit) {
                array_shift($this->internalCachedMappings);
            }
        }

        return $this->internalCachedMappings[$className];
    }

}
