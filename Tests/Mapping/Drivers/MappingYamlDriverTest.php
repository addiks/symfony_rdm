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

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use ReflectionProperty;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Addiks\RDMBundle\Mapping\Drivers\MappingYamlDriver;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingYamlDriverTest extends TestCase
{

    /**
     * @var MappingYamlDriver
     */
    private $mappingDriver;

    /**
     * @var MappingDriverInterface
     */
    private $fileLocator;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->fileLocator = $this->createMock(FileLocator::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->mappingDriver = new MappingYamlDriver(
            $this->container,
            $this->fileLocator
        );
    }

    /**
     * @test
     */
    public function shouldReadMappingData()
    {
        /** @var string $mappingFilePath */
        $mappingFilePath = __DIR__ . "/EntityExample.orm.yml";

        $expectedMapping = new EntityMapping(EntityExample::class, [
            'foo' => new ServiceMapping($this->container, 'some_service', false, "in file '{$mappingFilePath}'"),
            'bar' => new ServiceMapping($this->container, 'other_service', false, "in file '{$mappingFilePath}'"),
            'baz' => new ChoiceMapping('baz_column', [
                'lorem' => new ServiceMapping($this->container, "lorem_service", false, "in file '{$mappingFilePath}'"),
                'ipsum' => new ServiceMapping($this->container, "ipsum_service", true, "in file '{$mappingFilePath}'"),
            ], "in file '{$mappingFilePath}'"),
        ]);

        $this->fileLocator->method('fileExists')->willReturn(true);
        $this->fileLocator->method('findMappingFile')->willReturn($mappingFilePath);

        /** @var EntityMapping $actualMapping */
        $actualMapping = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedMapping, $actualMapping);
    }

    /**
     * @test
     */
    public function shouldNotReadOtherEntitiesMappingData()
    {
        /** @var string $mappingFilePath */
        $mappingFilePath = __DIR__ . "/EntityExample.orm.yml";

        $this->fileLocator->method('fileExists')->willReturn(true);
        $this->fileLocator->method('findMappingFile')->willReturn($mappingFilePath);

        /** @var EntityMapping $actualMapping */
        $actualMapping = $this->mappingDriver->loadRDMMetadataForClass(
            get_class($this->createMock(EntityExample::class))
        );

        $this->assertNull($actualMapping);
    }

}
