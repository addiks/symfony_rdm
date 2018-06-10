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
use Addiks\RDMBundle\Mapping\Drivers\MappingAnnotationDriver;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use ReflectionProperty;
use Doctrine\Common\Annotations\Reader;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\Annotation\Choice;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Doctrine\DBAL\Schema\Column as DBALColumn;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\Annotation\Obj;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Doctrine\ORM\Mapping\Column as ORMColumn;
use Addiks\RDMBundle\Mapping\FieldMapping;
use Addiks\RDMBundle\Mapping\ArrayMapping;
use Addiks\RDMBundle\Mapping\Annotation\RDMObject;
use Addiks\RDMBundle\Mapping\Annotation\RDMArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingAnnotationDriverTest extends TestCase
{

    /**
     * @var MappingAnnotationDriver
     */
    private $mappingDriver;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->annotationReader = $this->createMock(Reader::class);

        $this->mappingDriver = new MappingAnnotationDriver(
            $this->container,
            $this->annotationReader
        );
    }

    /**
     * @test
     */
    public function shouldReadAnnotations()
    {
        $someAnnotationA = new Service();
        $someAnnotationA->id = "some_service";
        $someAnnotationA->field = "foo";

        $someAnnotationB = new Service();
        $someAnnotationB->id = "other_service";
        $someAnnotationB->field = "bar";
        $someAnnotationB->lax = true;

        $someAnnotationC = new Choice();
        $someAnnotationC->column = "baz_column";
        $someAnnotationC->nullable = true;
        $someAnnotationC->choices = [
            'foo' => $someAnnotationA,
            'bar' => $someAnnotationB,
        ];

        $someAnnotationD = new Choice();
        $someAnnotationD->column = "faz_column";
        $someAnnotationD->nullable = false;
        $someAnnotationD->choices = [
            'foo' => $someAnnotationA,
            'bar' => $someAnnotationB,
        ];

        $someAnnotationF = new ORMColumn();
        $someAnnotationF->name = "someField";
        $someAnnotationF->length = 12;

        $someAnnotationE = new RDMObject();
        $someAnnotationE->{"class"} = ValueObjectExample::class;
        $someAnnotationE->fields = [
            'foo' => $someAnnotationA,
            'bar' => $someAnnotationF,
        ];

        $someAnnotationG = new RDMArray();
        $someAnnotationG->entries = [
            'foo' => $someAnnotationA,
            'bar' => $someAnnotationF,
        ];

        /** @var string $entityClass */
        $entityClass = EntityExample::class;

        /** @var array<MappingInterface> $expectedFieldMappings */
        $expectedFieldMappings = [
            'foo' => new ServiceMapping(
                $this->container,
                "some_service",
                false,
                "in entity '{$entityClass}' on field 'foo'"
            ),
            'bar' => new ServiceMapping(
                $this->container,
                "other_service",
                true,
                "in entity '{$entityClass}' on field 'bar'"
            ),
            'baz' => new ChoiceMapping('baz_column', [
                'foo' => new ServiceMapping(
                    $this->container,
                    "some_service",
                    false,
                    "in entity '{$entityClass}' on field 'baz'"
                ),
                'bar' => new ServiceMapping(
                    $this->container,
                    "other_service",
                    true,
                    "in entity '{$entityClass}' on field 'baz'"
                ),
            ], "in entity 'Addiks\RDMBundle\Tests\Hydration\EntityExample' on field 'baz'"),
            'faz' => new ChoiceMapping(new DBALColumn('faz_column', Type::getType('string'), [
                'notnull' => true,
                'length' => 255,
            ]), [
                'foo' => new ServiceMapping(
                    $this->container,
                    "some_service",
                    false,
                    "in entity '{$entityClass}' on field 'faz'"
                ),
                'bar' => new ServiceMapping(
                    $this->container,
                    "other_service",
                    true,
                    "in entity '{$entityClass}' on field 'faz'"
                ),
            ], "in entity 'Addiks\RDMBundle\Tests\Hydration\EntityExample' on field 'faz'"),
            'boo' => new ObjectMapping(ValueObjectExample::class, [
                'foo' => new ServiceMapping(
                    $this->container,
                    "some_service",
                    false,
                    "in entity '{$entityClass}' on field 'boo->foo'"
                ),
                'bar' => new FieldMapping(new DBALColumn('someField', Type::getType('string'), [
                    'notnull' => true,
                    'precision' => 0,
                    'length' => 12,
                ]), "in entity '{$entityClass}' on field 'boo->bar'"),
            ], null, "in entity 'Addiks\RDMBundle\Tests\Hydration\EntityExample' on field 'boo'"),
            'arr' => new ArrayMapping([
                'foo' => new ServiceMapping(
                    $this->container,
                    "some_service",
                    false,
                    "in entity '{$entityClass}' on field 'arr->foo'"
                ),
                'bar' => new FieldMapping(new DBALColumn('someField', Type::getType('string'), [
                    'notnull' => true,
                    'precision' => 0,
                    'length' => 12,
                ]), "in entity '{$entityClass}' on field 'arr->bar'"),
            ], "in entity 'Addiks\RDMBundle\Tests\Hydration\EntityExample' on field 'arr'")
        ];

        /** @var array<array<Service>> $annotationMap */
        $annotationMap = [
            'foo' => [$someAnnotationA],
            'bar' => [$someAnnotationB],
            'baz' => [$someAnnotationC],
            'faz' => [$someAnnotationD],
            'boo' => [$someAnnotationE],
            'arr' => [$someAnnotationG],
        ];

        $this->annotationReader->method('getPropertyAnnotations')->will($this->returnCallback(
            function (ReflectionProperty $propertyReflection) use ($annotationMap) {
                if (isset($annotationMap[$propertyReflection->getName()])) {
                    return $annotationMap[$propertyReflection->getName()];
                } else {
                    return [];
                }
            }
        ));

        /** @var EntityMapping $actualMapping */
        $actualMapping = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedFieldMappings, $actualMapping->getFieldMappings());
    }

}
