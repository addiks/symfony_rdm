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
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryAggregate;

final class MappingDriverFactoryAggregateTest extends TestCase
{

    /**
     * @var MappingDriverFactoryAggregate
     */
    private $driverFactory;

    /**
     * @var MappingDriverFactoryInterface
     */
    private $innerMappingDriverFactoryA;

    /**
     * @var MappingDriverFactoryInterface
     */
    private $innerMappingDriverFactoryB;

    /**
     * @var MappingDriverFactoryInterface
     */
    private $innerMappingDriverFactoryC;

    public function setUp(): void
    {
        $this->innerMappingDriverFactoryA = $this->createMock(MappingDriverFactoryInterface::class);
        $this->innerMappingDriverFactoryB = $this->createMock(MappingDriverFactoryInterface::class);
        $this->innerMappingDriverFactoryC = $this->createMock(MappingDriverFactoryInterface::class);

        $this->driverFactory = new MappingDriverFactoryAggregate([
            $this->innerMappingDriverFactoryA,
            $this->innerMappingDriverFactoryB,
            $this->innerMappingDriverFactoryC,
        ]);
    }

    /**
     * @test
     */
    public function shouldCreatedMappingDriver()
    {
        /** @var MappingDriver $mappingDriver */
        $mappingDriver = $this->createMock(MappingDriver::class);

        /** @var MappingDriverInterface $expectedRdmMappingDriver */
        $expectedRdmMappingDriver = $this->createMock(MappingDriverInterface::class);

        /** @var MappingDriverInterface $anotherRdmMappingDriver */
        $anotherRdmMappingDriver = $this->createMock(MappingDriverInterface::class);

        $this->innerMappingDriverFactoryA->method('createRDMMappingDriver')->willReturn(null);
        $this->innerMappingDriverFactoryB->method('createRDMMappingDriver')->willReturn($expectedRdmMappingDriver);
        $this->innerMappingDriverFactoryC->method('createRDMMappingDriver')->willReturn($anotherRdmMappingDriver);

        /** @var RdmMappingDriverChain $actualRdmMappingDriver */
        $actualRdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertSame($expectedRdmMappingDriver, $actualRdmMappingDriver);
    }

}
