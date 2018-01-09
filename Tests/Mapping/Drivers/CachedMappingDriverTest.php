<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
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

    public function setUp()
    {
        $this->innerMappingDriver = $this->createMock(MappingDriverInterface::class);
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);

        $this->mappingDriver = new CachedMappingDriver(
            $this->innerMappingDriver,
            $this->cacheItemPool
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

        /** @var array<mixed> $expectedAnnotations */
        $expectedAnnotations = ["Lorem ipsum", "dolor sit"];

        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn(serialize(["Lorem ipsum", "dolor sit"]));

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

        /** @var array<mixed> $expectedAnnotations */
        $expectedAnnotations = ["Lorem ipsum", "dolor sit"];

        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with(serialize(["Lorem ipsum", "dolor sit"]));

        $this->innerMappingDriver->method('loadRDMMetadataForClass')->will($this->returnValueMap([
            [EntityExample::class, $expectedAnnotations]
        ]));

        /** @var array<mixed> $actualAnnotations */
        $actualAnnotations = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedAnnotations, $actualAnnotations);
    }

}
