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

    public function setUp()
    {
        $this->fileLocator = $this->createMock(FileLocator::class);
        $this->kernel = $this->createMock(KernelInterface::class);

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
            'foo' => new ServiceMapping('some_service', false, "in file '{$mappingFilePath}'"),
            'bar' => new ServiceMapping('other_service', false, "in file '{$mappingFilePath}'"),
            'baz' => new ChoiceMapping('baz_column', [
                'lorem' => new ServiceMapping("lorem_service", false, "in file '{$mappingFilePath}'"),
                'ipsum' => new ServiceMapping("ipsum_service", true,  "in file '{$mappingFilePath}'"),
            ], "in file '{$mappingFilePath}'"),
            'faz' => new ChoiceMapping(new Column("faz_column", Type::getType('string'), ['notnull' => true]), [
                'lorem' => new ServiceMapping("lorem_service", false, "in file '{$mappingFilePath}'"),
                'ipsum' => new ServiceMapping("ipsum_service", false, "in file '{$mappingFilePath}'"),
            ], "in file '{$mappingFilePath}'"),
            'far' => new ChoiceMapping(new Column("far_column", Type::getType('string'), ['notnull' => false]), [
                'lorem' => new ServiceMapping("lorem_service", false, "in file '{$mappingFilePath}'"),
                'ipsum' => new ServiceMapping("ipsum_service", false, "in file '{$mappingFilePath}'"),
                'dolor' => new ObjectMapping(
                    ValueObjectExample::class,
                    [],
                    null,
                    "in file '{$mappingFilePath}'",
                    new CallDefinition("createFromJson", "self", [], true),
                    new CallDefinition("serializeJson")
                ),
            ], "in file '{$mappingFilePath}'"),
            'boo' => new ObjectMapping(ValueObjectExample::class, [
                'scalarValue' => new FieldMapping(
                    new Column("scalarValue", Type::getType('string'), ['notnull' => false]),
                    "in file '{$mappingFilePath}'"
                ),
                'lorem' => new FieldMapping(
                    new Column("lorem", Type::getType('string'), ['notnull' => false]),
                    "in file '{$mappingFilePath}'"
                ),
                'dolor' => new FieldMapping(
                    new Column("dolor", Type::getType('integer'), ['notnull' => false]),
                    "in file '{$mappingFilePath}'"
                ),
            ], null, "in file '{$mappingFilePath}'"),
            'abc' => new ObjectMapping(
                ValueObjectExample::class,
                [],
                null,
                "in file '{$mappingFilePath}'",
                new CallDefinition("createFromJson", "self", [], true),
                new CallDefinition("serializeJson")
            ),
            'def' => new ObjectMapping(
                ValueObjectExample::class,
                [
                    'lorem' => new FieldMapping(
                        new Column("lorem", Type::getType('string'), ['notnull' => false]),
                        "in file '{$mappingFilePath}'"
                    ),
                    'dolor' => new FieldMapping(
                        new Column("dolor", Type::getType('integer'), ['notnull' => false]),
                        "in file '{$mappingFilePath}'"
                    ),
                ],
                null,
                "in file '{$mappingFilePath}'",
                new CallDefinition("createValueObject", "@value_object.factory", [
                    new FieldMapping(
                        new Column("def", Type::getType('integer'), ['notnull' => false]),
                        "in file '{$mappingFilePath}'"
                    )
                ])
            ),
            'ghi' => new ObjectMapping(
                ValueObjectExample::class,
                [],
                null,
                "in file '{$mappingFilePath}'",
                new CallDefinition("createValueObject", "@value_object.factory", [
                    new ChoiceMapping('baz_column', [
                        'lorem' => new ServiceMapping("lorem_service", false, "in file '{$mappingFilePath}'"),
                        'ipsum' => new ServiceMapping("ipsum_service", true,  "in file '{$mappingFilePath}'"),
                    ], "in file '{$mappingFilePath}'")
                ])
            ),
            'jkl' => new ArrayMapping(
                [
                    new ObjectMapping(ValueObjectExample::class, [], null, "in file '{$mappingFilePath}'"),
                    new ObjectMapping(ValueObjectExample::class, [
                        'qwe' => new ArrayMapping([], "in file '{$mappingFilePath}'")
                    ], null, "in file '{$mappingFilePath}'"),
                    new ObjectMapping(ValueObjectExample::class, [], null, "in file '{$mappingFilePath}'"),
                ],
                "in file '{$mappingFilePath}'"
            ),
            'mno' => new ArrayMapping(
                [
                    'foo' => new ServiceMapping('some_service', false, "in file '{$mappingFilePath}'"),
                    'bar' => new ServiceMapping('other_service', false, "in file '{$mappingFilePath}'"),
                    'baz' => new NullMapping("in file '{$mappingFilePath}'"),
                    'maz' => new ListMapping(
                        new Column("maz_column", Type::getType('string'), ['notnull' => true]),
                        new ObjectMapping(
                            ValueObjectExample::class,
                            [],
                            new Column("maz_obj_column", Type::getType('string')),
                            "in file '{$mappingFilePath}'"
                        ),
                        "in file '{$mappingFilePath}'"
                    ),
                ],
                "in file '{$mappingFilePath}'"
            ),
            'pqr' => new NullableMapping(
                new ServiceMapping('some_service', false, "in file '{$mappingFilePath}'"),
                new Column("pqr_column", Type::getType('boolean'), ['notnull' => false]),
                "in file '{$mappingFilePath}'"
            ),
            'stu' => new ListMapping(
                new Column("stu_column", Type::getType('string'), ['notnull' => true]),
                new ObjectMapping(ValueObjectExample::class, [], null, "in file '{$mappingFilePath}'"),
                "in file '{$mappingFilePath}'"
            ),
            'vwx' => new ObjectMapping(ValueObjectExample::class, [
                'foo' => new ServiceMapping('some_service', false, "in file '{$mappingImportFilePath}'"),
                'bar' => new ServiceMapping('other_service', false, "in file '{$mappingImportFilePath}'"),
            ], new Column("vwx_column", Type::getType('integer'), ['notnull' => true]), "in file '{$mappingImportFilePath}'")
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
