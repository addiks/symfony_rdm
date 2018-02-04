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

final class MappingAnnotationDriverTest extends TestCase
{

    /**
     * @var MappingAnnotationDriver
     */
    private $mappingDriver;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function setUp()
    {
        $this->annotationReader = $this->createMock(Reader::class);

        $this->mappingDriver = new MappingAnnotationDriver(
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

        /** @var string $entityClass */
        $entityClass = EntityExample::class;

        /** @var array<ServiceMapping> $expectedFieldMappings */
        $expectedFieldMappings = [
            'foo' => new ServiceMapping("some_service", false, "in entity '{$entityClass}' on field 'foo'"),
            'bar' => new ServiceMapping("other_service", false, "in entity '{$entityClass}' on field 'bar'")
        ];

        /** @var array<array<Service>> $annotationMap */
        $annotationMap = [
            'foo' => [$someAnnotationA],
            'bar' => [$someAnnotationB],
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
