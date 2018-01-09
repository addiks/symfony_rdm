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
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingStaticPHPDriverFactory;
use Addiks\RDMBundle\Mapping\Drivers\MappingStaticPHPDriver;
use Doctrine\ORM\Mapping\Driver\StaticPHPDriver;

final class MappingStaticPHPDriverFactoryTest extends TestCase
{

    /**
     * @var MappingStaticPHPDriverFactory
     */
    private $driverFactory;

    public function setUp()
    {
        $this->driverFactory = new MappingStaticPHPDriverFactory();
    }

    /**
     * @test
     */
    public function shouldCreatedAnnotationMappingDriver()
    {
        /** @var StaticPHPDriver $mappingDriver */
        $mappingDriver = $this->createMock(StaticPHPDriver::class);

        /** @var MappingStaticPHPDriver $rdmMappingDriver */
        $rdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertInstanceOf(MappingStaticPHPDriver::class, $rdmMappingDriver);
    }

}
