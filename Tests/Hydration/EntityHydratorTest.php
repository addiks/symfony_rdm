<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Hydration;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Hydration\EntityHydrator;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use ErrorException;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;

final class EntityHydratorTest extends TestCase
{

    /**
     * @var EntityHydrator
     */
    private $hydrator;

    /**
     * @var ValueResolverInterface
     */
    private $valueResolver;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var DataLoaderInterface
     */
    private $dbalDataLoader;

    public function setUp()
    {
        $this->valueResolver = $this->createMock(ValueResolverInterface::class);
        $this->mappingDriver = $this->createMock(MappingDriverInterface::class);
        $this->dbalDataLoader = $this->createMock(DataLoaderInterface::class);

        $this->hydrator = new EntityHydrator(
            $this->valueResolver,
            $this->mappingDriver,
            $this->dbalDataLoader
        );
    }

    /**
     * @test
     */
    public function shouldHydrateAnEntityWithServices()
    {
        $fooMapping = new ServiceMapping("the_foo_service");
        $barMapping = new ServiceMapping("another_bar_service");

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn(
            new EntityMapping(EntityExample::class, [
                'foo' => $fooMapping,
                'bar' => $barMapping
            ])
        );

        $serviceA = new ServiceExample("SomeService", 123);
        $serviceB = new ServiceExample("AnotherService", 456);

        $entity = new EntityExample();

        $this->valueResolver->method("resolveValue")->will($this->returnValueMap([
            [$fooMapping, $entity, [], $serviceA],
            [$barMapping, $entity, [], $serviceB],
        ]));

        $this->hydrator->hydrateEntity($entity, $this->createMock(EntityManagerInterface::class));

        $this->assertEquals($serviceA, $entity->foo);
        $this->assertEquals($serviceB, $entity->bar);
    }

    /**
     * @test
     */
    public function shouldAssertHydrationUsingValueResolvers()
    {
        $fooMapping = new ServiceMapping("the_foo_service");

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn(
            new EntityMapping(EntityExample::class, [
                'foo' => $fooMapping,
            ])
        );

        $this->valueResolver->expects($this->once())->method("assertValue");

        $entity = new EntityExample();

        $this->hydrator->assertHydrationOnEntity($entity, $this->createMock(EntityManagerInterface::class));
    }

}
