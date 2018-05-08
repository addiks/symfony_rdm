<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\ValueResolver;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\ValueResolver\ServiceValueResolver;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Mapping\ServiceMappingInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;

final class ServiceValueResolverTest extends TestCase
{

    /**
     * @var ServiceValueResolver
     */
    private $valueResolver;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->valueResolver = new ServiceValueResolver($this->container);
    }

    /**
     * @test
     */
    public function shouldResolveAValue()
    {
        $expectedService = new ServiceExample("lorem", 123);

        $this->container->method('has')->will($this->returnValueMap([
            ['some_service', true],
        ]));

        $this->container->method('get')->will($this->returnValueMap([
            ['some_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $expectedService],
        ]));

        /** @var ServiceMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(ServiceMappingInterface::class);

        $fieldMapping->method('getServiceId')->willReturn("some_service");

        /** @var mixed $actualValue */
        $actualService = $this->valueResolver->resolveValue(
            $fieldMapping,
            $this->createMock(HydrationContextInterface::class),
            []
        );

        $this->assertSame($expectedService, $actualService);
    }

    /**
     * @test
     */
    public function shouldRevertValue()
    {
        /** @var ServiceMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(ServiceMappingInterface::class);

        $this->assertEquals([], $this->valueResolver->revertValue(
            $fieldMapping,
            $this->createMock(HydrationContextInterface::class),
            null
        ));
    }

    /**
     * @test
     */
    public function shouldAssertThatCorrectServiceWasSet()
    {
        /** @var ServiceMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(ServiceMappingInterface::class);

        $fieldMapping->method('isLax')->willReturn(false);
        $fieldMapping->method('getServiceId')->willReturn("some_service");

        $service = new ServiceExample("lorem", 123);
        $otherService = new ServiceExample("ipsum", 456);

        $this->container->method('has')->will($this->returnValueMap([
            ['some_service', true],
        ]));

        $this->container->method('get')->will($this->returnValueMap([
            ['some_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $service],
        ]));

        $this->expectException(FailedRDMAssertionExceptionInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        $this->valueResolver->assertValue($fieldMapping, $context, [], $otherService);
    }

    /**
     * @test
     */
    public function shouldNotAssertLaxFieldMappings()
    {
        /** @var ServiceMappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(ServiceMappingInterface::class);

        $fieldMapping->method('isLax')->willReturn(true);

        $fieldMapping->expects($this->never())->method('getServiceId');

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        $service = new ServiceExample("lorem", 123);

        $this->valueResolver->assertValue($fieldMapping, $context, [], $service);
    }

}
