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
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Addiks\RDMBundle\Mapping\Drivers\MappingXmlDriver;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use Addiks\RDMBundle\Mapping\CallDefinition;
use Addiks\RDMBundle\Mapping\FieldMapping;
use Addiks\RDMBundle\Mapping\ArrayMapping;
use Symfony\Component\HttpKernel\KernelInterface;
use Addiks\RDMBundle\Mapping\NullMapping;
use Addiks\RDMBundle\Mapping\NullableMapping;
use Addiks\RDMBundle\Mapping\ListMapping;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingXmlDriverTest extends TestCase
{

    /**
     * @var MappingXmlDriver
     */
    private $mappingDriver;

    /**
     * @var MappingDriverInterface
     */
    private $fileLocator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        $this->fileLocator = $this->createMock(FileLocator::class);
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->kernel->method("getContainer")->willReturn($this->container);

        $this->mappingDriver = new MappingXmlDriver(
            $this->fileLocator,
            $this->kernel,
            realpath(__DIR__ . "/../../../Resources/mapping-schema.v1.xsd")
        );
    }

    /**
     * @test
     */
    public function shouldReadMappingData()
    {
        /** @var string $mappingFilePath */
        $mappingFilePath = __DIR__ . "/EntityExample.orm.xml";

        /** @var string $mappingImportFilePath */
        $mappingImportFilePath = __DIR__ . "/EntityExampleImport.orm.xml";

        $expectedMapping = new EntityMapping(EntityExample::class, [
            'foo' => new ServiceMapping($this->container, 'some_service', false, "in file '{$mappingFilePath}' in line 14"),
            'bar' => new ServiceMapping($this->container, 'other_service', false, "in file '{$mappingFilePath}' in line 15"),
            'baz' => new ChoiceMapping('baz_column', [
                'lorem' => new ServiceMapping($this->container, "lorem_service", false, "in file '{$mappingFilePath}' in line 19"),
                'ipsum' => new ServiceMapping($this->container, "ipsum_service", true, "in file '{$mappingFilePath}' in line 22"),
            ], "in file '{$mappingFilePath}' in line 17"),
            'faz' => new ChoiceMapping(new Column("faz_column", Type::getType('string'), ['notnull' => true]), [
                'lorem' => new ServiceMapping($this->container, "lorem_service", false, "in file '{$mappingFilePath}' in line 29"),
                'ipsum' => new ServiceMapping($this->container, "ipsum_service", false, "in file '{$mappingFilePath}' in line 32"),
            ], "in file '{$mappingFilePath}' in line 26"),
            'far' => new ChoiceMapping(new Column("far_column", Type::getType('string'), ['notnull' => false]), [
                'lorem' => new ServiceMapping($this->container, "lorem_service", false, "in file '{$mappingFilePath}' in line 42"),
                'ipsum' => new ServiceMapping($this->container, "ipsum_service", false, "in file '{$mappingFilePath}' in line 45"),
                'dolor' => new ObjectMapping(
                    ValueObjectExample::class,
                    [],
                    null,
                    "in file '{$mappingFilePath}' in line 52",
                    new CallDefinition($this->container, "createFromJson", "self", [], true, "{$mappingFilePath} in line 52"),
                    new CallDefinition($this->container, "serializeJson", null, [], false, "{$mappingFilePath} in line 52")
                ),
            ], "in file '{$mappingFilePath}' in line 39"),
            'boo' => new ObjectMapping(ValueObjectExample::class, [
                'scalarValue' => new FieldMapping(
                    new Column("scalarValue", Type::getType('string'), ['notnull' => false]),
                    "in file '{$mappingFilePath}' in line 57"
                ),
                'lorem' => new FieldMapping(
                    new Column("lorem", Type::getType('string'), ['notnull' => false]),
                    "in file '{$mappingFilePath}' in line 58"
                ),
                'dolor' => new FieldMapping(
                    new Column("dolor", Type::getType('integer'), ['notnull' => false]),
                    "in file '{$mappingFilePath}' in line 59"
                ),
            ], null, "in file '{$mappingFilePath}' in line 56"),
            'abc' => new ObjectMapping(
                ValueObjectExample::class,
                [],
                null,
                "in file '{$mappingFilePath}' in line 67",
                new CallDefinition($this->container, "createFromJson", "self", [], true, "{$mappingFilePath} in line 67"),
                new CallDefinition($this->container, "serializeJson", null, [], false, "{$mappingFilePath} in line 67")
            ),
            'def' => new ObjectMapping(
                ValueObjectExample::class,
                [
                    'lorem' => new FieldMapping(
                        new Column("lorem", Type::getType('string'), ['notnull' => false]),
                        "in file '{$mappingFilePath}' in line 76"
                    ),
                    'dolor' => new FieldMapping(
                        new Column("dolor", Type::getType('integer'), ['notnull' => false]),
                        "in file '{$mappingFilePath}' in line 77"
                    ),
                ],
                null,
                "in file '{$mappingFilePath}' in line 72",
                new CallDefinition($this->container, "createValueObject", "@value_object.factory", [
                    new FieldMapping(
                        new Column("def", Type::getType('integer'), ['notnull' => false]),
                        "in file '{$mappingFilePath}' in line 74"
                    )
                ], false, "{$mappingFilePath} in line 72")
            ),
            'ghi' => new ObjectMapping(
                ValueObjectExample::class,
                [],
                null,
                "in file '{$mappingFilePath}' in line 83",
                new CallDefinition($this->container, "createValueObject", "@value_object.factory", [
                    new ChoiceMapping('baz_column', [
                        'lorem' => new ServiceMapping(
                            $this->container,
                            "lorem_service",
                            false,
                            "in file '{$mappingFilePath}' in line 87"
                        ),
                        'ipsum' => new ServiceMapping(
                            $this->container,
                            "ipsum_service",
                            true,
                            "in file '{$mappingFilePath}' in line 90"
                        ),
                    ], "in file '{$mappingFilePath}' in line 85")
                ], false, "{$mappingFilePath} in line 83")
            ),
            'jkl' => new ArrayMapping(
                [
                    new ObjectMapping(ValueObjectExample::class, [], null, "in file '{$mappingFilePath}' in line 97"),
                    new ObjectMapping(ValueObjectExample::class, [
                        'qwe' => new ArrayMapping([], "in file '{$mappingFilePath}' in line 99")
                    ], null, "in file '{$mappingFilePath}' in line 98"),
                    new ObjectMapping(ValueObjectExample::class, [], null, "in file '{$mappingFilePath}' in line 101"),
                ],
                "in file '{$mappingFilePath}' in line 96"
            ),
            'mno' => new ArrayMapping(
                [
                    'foo' => new ServiceMapping(
                        $this->container,
                        'some_service',
                        false,
                        "in file '{$mappingFilePath}' in line 106"
                    ),
                    'bar' => new ServiceMapping(
                        $this->container,
                        'other_service',
                        false,
                        "in file '{$mappingFilePath}' in line 109"
                    ),
                    'baz' => new NullMapping("in file '{$mappingFilePath}' in line 113"),
                    'maz' => new ListMapping(
                        new Column("maz_column", Type::getType('string'), ['notnull' => true]),
                        new ObjectMapping(
                            ValueObjectExample::class,
                            [],
                            new Column("maz_obj_column", Type::getType('string'), [
                                'length' => 255,
                                'default' => '#DEFAULT#'
                            ]),
                            "in file '{$mappingFilePath}' in line 121"
                        ),
                        "in file '{$mappingFilePath}' in line 116"
                    ),
                ],
                "in file '{$mappingFilePath}' in line 104"
            ),
            'pqr' => new NullableMapping(
                new ServiceMapping($this->container, 'some_service', false, "in file '{$mappingFilePath}' in line 127"),
                new Column("pqr_column", Type::getType('boolean'), ['notnull' => false]),
                "in file '{$mappingFilePath}' at line 126"
            ),
            'stu' => new ListMapping(
                new Column("stu_column", Type::getType('string'), ['notnull' => true]),
                new ObjectMapping(ValueObjectExample::class, [], null, "in file '{$mappingFilePath}' in line 131"),
                "in file '{$mappingFilePath}' in line 130"
            ),
            'vwx' => new ObjectMapping(ValueObjectExample::class, [
                'foo' => new ServiceMapping(
                    $this->container,
                    'some_service',
                    false,
                    "in file '{$mappingImportFilePath}' in line 9"
                ),
                'bar' => new ServiceMapping(
                    $this->container,
                    'other_service',
                    false,
                    "in file '{$mappingImportFilePath}' in line 10"
                ),
            ], new Column(
                "vwx_column",
                Type::getType('integer'),
                ['notnull' => true, 'length' => 255]
            ), "in file '{$mappingImportFilePath}' in line 8")
        ]);

        $this->fileLocator->method('fileExists')->willReturn(true);
        $this->fileLocator->method('findMappingFile')->willReturn("@foobarbaz");

        $this->kernel->method("locateResource")->willReturn($mappingFilePath);

        /** @var EntityMapping $actualMapping */
        $actualMapping = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedMapping, $actualMapping);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnBrokenImport()
    {
        $this->expectException(InvalidMappingException::class);

        /** @var string $mappingFilePath */
        $mappingFilePath = __DIR__ . "/EntityExampleBroken.orm.xml";

        $this->fileLocator->method('fileExists')->willReturn(true);
        $this->fileLocator->method('findMappingFile')->willReturn($mappingFilePath);

        $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);
    }

}
