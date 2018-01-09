<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping\DriverFactories;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverChainFactory;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverChain as RdmMappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain as DoctrineMappingDriverChain;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;

final class MappingDriverChainFactoryTest extends TestCase
{

    /**
     * @var MappingDriverChainFactory
     */
    private $driverFactory;

    /**
     * @var MappingDriverFactoryInterface
     */
    private $rootMetadataDriverFactory;

    public function setUp()
    {
        $this->rootMetadataDriverFactory = $this->createMock(MappingDriverFactoryInterface::class);

        $this->driverFactory = new MappingDriverChainFactory(
            $this->rootMetadataDriverFactory
        );
    }

    /**
     * @test
     */
    public function shouldCreatedChainedMappingDriver()
    {
        /** @var DoctrineMappingDriverChain $mappingDriver */
        $mappingDriver = $this->createMock(DoctrineMappingDriverChain::class);

        /** @var MappingDriver $innerDriverA */
        $innerDriverA = $this->createMock(MappingDriver::class);

        /** @var MappingDriver $innerDriverB */
        $innerDriverB = $this->createMock(MappingDriver::class);

        /** @var MappingDriverInterface $innerRdmMappingDriverA */
        $innerRdmMappingDriverA = $this->createMock(MappingDriverInterface::class);

        /** @var MappingDriverInterface $innerRdmMappingDriverB */
        $innerRdmMappingDriverB = $this->createMock(MappingDriverInterface::class);

        $innerRdmMappingDriverA->expects($this->once())->method('loadRDMMetadataForClass');
        $innerRdmMappingDriverB->expects($this->once())->method('loadRDMMetadataForClass');

        $this->rootMetadataDriverFactory->method('createRDMMappingDriver')->will($this->returnValueMap([
            [$innerDriverA, $innerRdmMappingDriverA],
            [$innerDriverB, $innerRdmMappingDriverB],
        ]));

        $mappingDriver->method('getDrivers')->willReturn([
            $innerDriverA,
            $innerDriverB
        ]);

        /** @var RdmMappingDriverChain $rdmMappingDriver */
        $rdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertInstanceOf(RdmMappingDriverChain::class, $rdmMappingDriver);

        $rdmMappingDriver->loadRDMMetadataForClass(EntityExample::class);
    }

}
