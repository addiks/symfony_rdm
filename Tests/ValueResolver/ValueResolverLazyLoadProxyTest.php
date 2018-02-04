<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\ValueResolver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\ValueResolver\ValueResolverLazyLoadProxy;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;

final class ValueResolverLazyLoadProxyTest extends TestCase
{

    /**
     * @var ValueResolverLazyLoadProxy
     */
    private $valueResolver;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->valueResolver = new ValueResolverLazyLoadProxy($this->container, "some_service");
    }

    /**
     * @test
     */
    public function loadsTheInnerResolver()
    {
        /** @var ValueResolverInterface $innerResolver */
        $innerResolver = $this->createMock(ValueResolverInterface::class);

        $this->container->method('get')->will($this->returnValueMap([
            ["some_service", ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $innerResolver],
        ]));

        /** @var MappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(MappingInterface::class);

        $entity = new EntityExample();

        /** @var array<scalar> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'lorem' => 'ipsum'
        ];

        $innerResolver->expects($this->once())->method("resolveValue")->with(
            $this->equalTo($fieldMapping),
            $this->equalTo($entity),
            $this->equalTo($dataFromAdditionalColumns)
        );

        $this->valueResolver->resolveValue(
            $fieldMapping,
            $entity,
            $dataFromAdditionalColumns
        );
    }

    /**
     * @test
     */
    public function revertsValueUsingInnerResolver()
    {
        /** @var ValueResolverInterface $innerResolver */
        $innerResolver = $this->createMock(ValueResolverInterface::class);

        $this->container->method('get')->will($this->returnValueMap([
            ["some_service", ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $innerResolver],
        ]));

        /** @var MappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(MappingInterface::class);

        $entity = new EntityExample();

        /** @var string $valueFromEntityField */
        $valueFromEntityField = 'lorem ipsum';

        $innerResolver->expects($this->once())->method("revertValue")->with(
            $this->equalTo($fieldMapping),
            $this->equalTo($entity),
            $this->equalTo($valueFromEntityField)
        );

        $this->valueResolver->revertValue(
            $fieldMapping,
            $entity,
            $valueFromEntityField
        );
    }

    /**
     * @test
     */
    public function letsInnerResolverAssertValues()
    {
        /** @var ValueResolverInterface $innerResolver */
        $innerResolver = $this->createMock(ValueResolverInterface::class);

        $this->container->method('get')->will($this->returnValueMap([
            ["some_service", ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $innerResolver],
        ]));

        /** @var MappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(MappingInterface::class);

        $entity = new EntityExample();

        /** @var array<scalar> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'lorem' => 'ipsum'
        ];

        /** @var string $actualValue */
        $actualValue = 'lorem ipsum';

        $innerResolver->expects($this->once())->method("assertValue")->with(
            $this->equalTo($fieldMapping),
            $this->equalTo($entity),
            $this->equalTo($dataFromAdditionalColumns),
            $this->equalTo($actualValue)
        );

        $this->valueResolver->assertValue(
            $fieldMapping,
            $entity,
            $dataFromAdditionalColumns,
            $actualValue
        );
    }

}
