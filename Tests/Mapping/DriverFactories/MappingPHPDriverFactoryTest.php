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
use Addiks\RDMBundle\Mapping\DriverFactories\MappingPHPDriverFactory;
use Doctrine\ORM\Mapping\Driver\PHPDriver;
use Addiks\RDMBundle\Mapping\Drivers\MappingPHPDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;

final class MappingPHPDriverFactoryTest extends TestCase
{

    /**
     * @var MappingPHPDriverFactory
     */
    private $driverFactory;

    public function setUp()
    {
        $this->driverFactory = new MappingPHPDriverFactory();
    }

    /**
     * @test
     */
    public function shouldCreatedPHPMappingDriver()
    {
        /** @var PHPDriver $mappingDriver */
        $mappingDriver = $this->createMock(PHPDriver::class);

        $mappingDriver->method('getLocator')->willReturn($this->createMock(FileLocator::class));

        /** @var MappingPHPDriver $rdmMappingDriver */
        $rdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertInstanceOf(MappingPHPDriver::class, $rdmMappingDriver);
    }

}
