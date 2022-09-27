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
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingYamlDriverFactory;
use Addiks\RDMBundle\Mapping\Drivers\MappingYamlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingYamlDriverFactoryTest extends TestCase
{

    /**
     * @var MappingYamlDriverFactory
     */
    private $driverFactory;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->driverFactory = new MappingYamlDriverFactory(
            $this->container
        );
    }

    /**
     * @test
     */
    public function shouldCreatedAnnotationMappingDriver()
    {
        /** @var YamlDriver $mappingDriver */
        $mappingDriver = $this->createMock(YamlDriver::class);

        $mappingDriver->method('getLocator')->willReturn($this->createMock(FileLocator::class));

        /** @var MappingYamlDriver $rdmMappingDriver */
        $rdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertInstanceOf(MappingYamlDriver::class, $rdmMappingDriver);
    }

}
