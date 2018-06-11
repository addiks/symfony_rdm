<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping\Drivers;

use Addiks\RDMBundle\Mapping\Drivers\CachedMappingDriver;
use Psr\Cache\CacheItemPoolInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Psr\Cache\CacheItemInterface;
use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class CachedMappingDriverTest extends TestCase
{

    /**
     * @var CachedMappingDriver
     */
    private $mappingDriver;

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

    public function setUp()
    {
        $this->innerMappingDriver = $this->createMock(MappingDriverInterface::class);
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->mappingDriver = new CachedMappingDriver(
            $this->innerMappingDriver,
            $this->container,
            $this->cacheItemPool,
            2
        );
    }

    /**
     * @test
     */
    public function shouldUseCachedMappingData()
    {
        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cacheItemPool->method('getItem')->will($this->returnValueMap([
            ['addiks_rdm_mapping__Addiks_RDMBundle_Tests_Hydration_EntityExample', $cacheItem]
        ]));

        /** @var EntityMapping $expectedAnnotations */
        $expectedAnnotations = new EntityMapping(EntityExample::class, []);

        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn(serialize($expectedAnnotations));

        /** @var array<mixed> $actualAnnotations */
        $actualAnnotations = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedAnnotations, $actualAnnotations);
    }

    /**
     * @test
     */
    public function shouldCacheMappingData()
    {
        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cacheItemPool->method('getItem')->will($this->returnValueMap([
            ['addiks_rdm_mapping__Addiks_RDMBundle_Tests_Hydration_EntityExample', $cacheItem]
        ]));

        /** @var EntityMapping $expectedAnnotations */
        $expectedAnnotations = new EntityMapping(EntityExample::class, []);

        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with(serialize($expectedAnnotations));

        $this->innerMappingDriver->method('loadRDMMetadataForClass')->will($this->returnValueMap([
            [EntityExample::class, $expectedAnnotations]
        ]));

        /** @var array<mixed> $actualAnnotations */
        $actualAnnotations = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedAnnotations, $actualAnnotations);
    }

    /**
     * @test
     */
    public function shouldHoldMappingsInInternalCache()
    {
        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $this->cacheItemPool->expects($this->exactly(4))->method('getItem')->will($this->returnValueMap([
            ['addiks_rdm_mapping__Addiks_RDMBundle_Tests_Hydration_EntityExample', $cacheItem],
            ['addiks_rdm_mapping__Addiks_RDMBundle_Tests_Hydration_ServiceExample', $cacheItem],
            ['addiks_rdm_mapping__Addiks_RDMBundle_Tests_ValueObjectExample', $cacheItem],
        ]));

        # For the following remember that only the last two classes will be held in internal cache.
        # (as defined in the setUp method above)

        $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class); # FIRST FETCH
        $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class); # (cached)

        $this->mappingDriver->loadRDMMetadataForClass(ServiceExample::class); # SECOND FETCH
        $this->mappingDriver->loadRDMMetadataForClass(ServiceExample::class); # (cached)

        $this->mappingDriver->loadRDMMetadataForClass(ValueObjectExample::class); # THIRD FETCH (removes EntityExample)
        $this->mappingDriver->loadRDMMetadataForClass(ValueObjectExample::class); # (cached)

        $this->mappingDriver->loadRDMMetadataForClass(ServiceExample::class); # (cached)
        $this->mappingDriver->loadRDMMetadataForClass(ServiceExample::class); # (cached)

        $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class); # FOURTH FETCH
        $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class); # (cached)
    }

}
