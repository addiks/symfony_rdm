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
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingXMLDriverFactory;
use Addiks\RDMBundle\Mapping\Drivers\MappingXmlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;

final class MappingXMLDriverFactoryTest extends TestCase
{

    /**
     * @var MappingXMLDriverFactory
     */
    private $driverFactory;

    public function setUp()
    {
        $this->driverFactory = new MappingXMLDriverFactory(
            "/some/schema/path.xsd"
        );
    }

    /**
     * @test
     */
    public function shouldCreatedAnnotationMappingDriver()
    {
        /** @var XmlDriver $mappingDriver */
        $mappingDriver = $this->createMock(XmlDriver::class);

        $mappingDriver->method('getLocator')->willReturn($this->createMock(FileLocator::class));

        /** @var MappingXmlDriver $rdmMappingDriver */
        $rdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertInstanceOf(MappingXmlDriver::class, $rdmMappingDriver);
    }

}
