<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Hydration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Hydration\EntityServiceHydrator;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use ErrorException;

final class EntityServiceHydratorTest extends TestCase
{

    /**
     * @var EntityServiceHydrator
     */
    private $hydrator;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->mappingDriver = $this->createMock(MappingDriverInterface::class);

        $this->hydrator = new EntityServiceHydrator(
            $this->container,
            $this->mappingDriver
        );
    }

    /**
     * @test
     */
    public function shouldHydrateAnEntityWithServices()
    {
        $serviceAnnotationA = new Service();
        $serviceAnnotationA->field = "foo";
        $serviceAnnotationA->id = "the_foo_service";

        $serviceAnnotationB = new Service();
        $serviceAnnotationB->field = "bar";
        $serviceAnnotationB->id = "another_bar_service";

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn([
            $serviceAnnotationA,
            $serviceAnnotationB
        ]);

        $serviceA = new ServiceExample("SomeService", 123);
        $serviceB = new ServiceExample("AnotherService", 456);

        $this->container->method("has")->will($this->returnValueMap([
            ['the_foo_service', true],
            ['another_bar_service', true],
        ]));

        $this->container->method("get")->will($this->returnValueMap([
            ['the_foo_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $serviceA],
            ['another_bar_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $serviceB],
        ]));

        $entity = new EntityExample();

        $this->hydrator->hydrateEntity($entity);

        $this->assertEquals($serviceA, $entity->foo);
        $this->assertEquals($serviceB, $entity->bar);
    }

    /**
     * @test
     */
    public function shouldRecognizeMissingServices()
    {
        $serviceAnnotation = new Service();
        $serviceAnnotation->field = "foo";
        $serviceAnnotation->id = "the_foo_service";

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn([
            $serviceAnnotation
        ]);

        $service = new ServiceExample("SomeService", 123);

        $this->container->method("has")->will($this->returnValueMap([
            ['the_foo_service', true],
        ]));

        $this->container->method("get")->will($this->returnValueMap([
            ['the_foo_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service],
        ]));

        $entity = new EntityExample();

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(sprintf(
            "Expected service %s (%s) to be in field %s of entity %s, was %s instead!",
            'the_foo_service',
            ServiceExample::class . "#" . spl_object_hash($service),
            'foo',
            EntityExample::class,
            'NULL'
        ));

        $this->hydrator->assertHydrationOnEntity($entity);
    }

}
