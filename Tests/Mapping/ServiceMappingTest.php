<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;

final class ServiceMappingTest extends TestCase
{

    /**
     * @var ServiceMapping
     */
    private $serviceMapping;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ServiceMapping
     */
    private $defaultServiceMapping;

    public function setUp(
        bool $isLax = true,
        string $serviceId = "some_service_id"
    ): void {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->serviceMapping = new ServiceMapping(
            $this->container,
            $serviceId,
            $isLax,
            "some origin"
        );

        $this->defaultServiceMapping = new ServiceMapping(
            $this->container,
            "some_default_service_id"
        );
    }

    /**
     * @test
     */
    public function shouldKnowItsServiceId()
    {
        $this->assertEquals("some_service_id", $this->serviceMapping->getServiceId());
    }

    /**
     * @test
     */
    public function shouldKnowItsOrigin()
    {
        $this->assertEquals("some origin", $this->serviceMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldHaveNoColumns()
    {
        $this->assertEquals([], $this->serviceMapping->collectDBALColumns());
    }

    /**
     * @test
     */
    public function shouldKnowIfLaxOrNot()
    {
        $this->assertEquals(true, $this->serviceMapping->isLax());
    }

    /**
     * @test
     */
    public function shouldBeNotLaxByDefault()
    {
        $this->assertEquals(false, $this->defaultServiceMapping->isLax());
    }

    /**
     * @test
     */
    public function shouldNotKnowItsOriginByDefault()
    {
        $this->assertEquals("unknown", $this->defaultServiceMapping->describeOrigin());
    }

    /**
     * @test
     */
    public function shouldResolveToService()
    {
        $this->setUp(false, "some_service");

        $expectedService = new ServiceExample("lorem", 123);

        $this->container->method('has')->will($this->returnValueMap([
            ['some_service', true],
        ]));

        $this->container->method('get')->will($this->returnValueMap([
            ['some_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $expectedService],
        ]));

        /** @var mixed $actualValue */
        $actualService = $this->serviceMapping->resolveValue(
            $this->createMock(HydrationContextInterface::class),
            []
        );

        $this->assertSame($expectedService, $actualService);
    }

    /**
     * @test
     */
    public function shouldNotRevertService()
    {
        $this->assertEquals([], $this->serviceMapping->revertValue(
            $this->createMock(HydrationContextInterface::class),
            null
        ));
    }

    /**
     * @test
     */
    public function shouldAssertThatCorrectServiceWasSet()
    {
        $this->setUp(false, "some_service");

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

        $this->serviceMapping->assertValue($context, [], $otherService);
    }

    /**
     * @test
     */
    public function shouldNotAssertLaxFieldMappings()
    {
        $this->setUp(true);

        $this->container->expects($this->never())->method('has');
        $this->container->expects($this->never())->method('get');

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        $service = new ServiceExample("lorem", 123);

        $this->serviceMapping->assertValue($context, [], $service);
    }

    /**
     * @test
     */
    public function shouldNotAssertIfAlwaysLaxModeIsActive()
    {
        $this->setUp(false);

        $this->container->expects($this->never())->method('has');
        $this->container->expects($this->never())->method('get');

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var mixed $service */
        $service = new ServiceExample("lorem", 123);

        ServiceMapping::setAlwaysLax(true);

        $this->serviceMapping->assertValue($context, [], $service);

        ServiceMapping::setAlwaysLax(false);
    }

    /**
     * @test
     */
    public function shouldWakeUpInnerMapping()
    {
        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);

        $container->method("has")->willReturn(true);
        $container->method("get")->willReturn("Foo bar baz");

        $this->serviceMapping->wakeUpMapping($container);

        $this->assertSame("Foo bar baz", $this->serviceMapping->resolveValue($context, []));
    }

}
