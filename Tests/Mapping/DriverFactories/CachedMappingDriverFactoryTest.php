<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping\DriverFactories;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\DriverFactories\CachedMappingDriverFactory;
use Psr\Cache\CacheItemPoolInterface;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Addiks\RDMBundle\Mapping\Drivers\CachedMappingDriver;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class CachedMappingDriverFactoryTest extends TestCase
{

    /**
     * @var CachedMappingDriverFactory
     */
    private $driverFactory;

    /**
     * @var MappingDriverFactoryInterface
     */
    private $innerMappingDriverFactory;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        $this->innerMappingDriverFactory = $this->createMock(MappingDriverFactoryInterface::class);
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->driverFactory = new CachedMappingDriverFactory(
            $this->container,
            $this->innerMappingDriverFactory,
            $this->cacheItemPool
        );
    }

    /**
     * @test
     */
    public function shouldCreatedCachedMappingDriver()
    {
        /** @var MappingDriver $mappingDriver */
        $mappingDriver = $this->createMock(MappingDriver::class);

        /** @var MappingDriverInterface $innerRdmMappingDriver */
        $innerRdmMappingDriver = $this->createMock(MappingDriverInterface::class);

        $this->innerMappingDriverFactory->method('createRDMMappingDriver')->willReturn($innerRdmMappingDriver);

        /** @var CachedMappingDriver $rdmMappingDriver */
        $rdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertInstanceOf(CachedMappingDriver::class, $rdmMappingDriver);
    }

}
