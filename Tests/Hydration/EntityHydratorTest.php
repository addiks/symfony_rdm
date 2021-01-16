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
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class EntityHydratorTest extends TestCase
{

    /**
     * @var EntityHydrator
     */
    private $hydrator;

    /**
     * @var MappingDriverInterface
     */
    private $mappingDriver;

    /**
     * @var DataLoaderInterface
     */
    private $dbalDataLoader;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        $this->mappingDriver = $this->createMock(MappingDriverInterface::class);
        $this->dbalDataLoader = $this->createMock(DataLoaderInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->hydrator = new EntityHydrator(
            $this->mappingDriver,
            $this->dbalDataLoader
        );
    }

    /**
     * @test
     */
    public function shouldHydrateAnEntityWithServices()
    {
        $fooMapping = $this->createMock(MappingInterface::class);
        $barMapping = $this->createMock(MappingInterface::class);
        $fazMapping = $this->createMock(MappingInterface::class);

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn(
            new EntityMapping(EntityExample::class, [
                'foo' => $fooMapping,
                'bar' => $barMapping,
                'faz' => $fazMapping
            ])
        );

        $serviceA = new ServiceExample("SomeService", 123);
        $serviceB = new ServiceExample("AnotherService", 456);
        $serviceC = new ServiceExample("PrivateService", 789);

        $entity = new EntityExample();

        $fooMapping->method("resolveValue")->willReturn($serviceA);
        $barMapping->method("resolveValue")->willReturn($serviceB);
        $fazMapping->method("resolveValue")->willReturn($serviceC);

        $this->hydrator->hydrateEntity($entity, $this->createMock(EntityManagerInterface::class));

        $this->assertEquals($serviceA, $entity->foo);
        $this->assertEquals($serviceB, $entity->bar);
        $this->assertEquals($serviceC, $entity->getFaz());
    }

    /**
     * @test
     */
    public function shouldAssertHydration()
    {
        $fazMapping = $this->createMock(MappingInterface::class);

        /** @var ServiceExample $fooService */
        $fazService = $this->createMock(ServiceExample::class);

        $entity = new EntityExample(null, null, null, $fazService);

        $this->mappingDriver->method("loadRDMMetadataForClass")->willReturn(
            new EntityMapping(EntityExample::class, [
                'faz' => $fazMapping,
            ])
        );

        $fazMapping->expects($this->once())->method("assertValue")->with(
            $this->isInstanceOf(HydrationContextInterface::class),
            $this->equalTo([]),
            $this->equalTo($fazService)
        );

        $this->hydrator->assertHydrationOnEntity($entity, $this->createMock(EntityManagerInterface::class));
    }

}
