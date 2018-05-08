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
use Addiks\RDMBundle\ValueResolver\ValueResolverAggregate;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;

final class ValueResolverAggregateTest extends TestCase
{

    /**
     * @var ValueResolverAggregate
     */
    private $valueResolver;

    /**
     * @var ValueResolverInterface
     */
    private $innerResolverA;

    /**
     * @var ValueResolverInterface
     */
    private $innerResolverB;

    public function setUp()
    {
        $this->innerResolverA = $this->createMock(ValueResolverInterface::class);
        $this->innerResolverB = $this->createMock(ValueResolverInterface::class);

        $this->valueResolver = new ValueResolverAggregate([
            ServiceMapping::class => $this->innerResolverA,
            ChoiceMapping::class  => $this->innerResolverB
        ]);
    }

    /**
     * @test
     */
    public function resolvesUsingTheCorrectResolver()
    {
        $fieldMapping = new ServiceMapping("some_service");

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var array<scalar> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'lorem' => 'ipsum'
        ];

        $this->innerResolverA->expects($this->once())->method("resolveValue")->with(
            $this->equalTo($fieldMapping),
            $this->equalTo($context),
            $this->equalTo($dataFromAdditionalColumns)
        );

        $this->innerResolverB->expects($this->never())->method("resolveValue");

        $this->valueResolver->resolveValue(
            $fieldMapping,
            $context,
            $dataFromAdditionalColumns
        );
    }

    /**
     * @test
     */
    public function revertsUsingTheCorrectResolver()
    {
        $fieldMapping = new ChoiceMapping("some_column", []);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var scalar $valueFromEntityField */
        $valueFromEntityField = 'lorem ipsum';

        $this->innerResolverA->expects($this->never())->method("revertValue");

        $this->innerResolverB->expects($this->once())->method("revertValue")->with(
            $this->equalTo($fieldMapping),
            $this->equalTo($context),
            $this->equalTo($valueFromEntityField)
        );

        $this->valueResolver->revertValue(
            $fieldMapping,
            $context,
            $valueFromEntityField
        );
    }

    /**
     * @test
     */
    public function assertsWithTheCorrectResolver()
    {
        $fieldMapping = new ChoiceMapping("some_column", []);

        /** @var HydrationContextInterface $context */
        $context = $this->createMock(HydrationContextInterface::class);
        $context->method('getEntityClass')->willReturn(EntityExample::class);

        /** @var array<scalar> $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = [
            'lorem' => 'ipsum'
        ];

        /** @var scalar $actualValue */
        $actualValue = 'lorem ipsum';

        $this->innerResolverA->expects($this->never())->method("assertValue");

        $this->innerResolverB->expects($this->once())->method("assertValue")->with(
            $this->equalTo($fieldMapping),
            $this->equalTo($context),
            $this->equalTo($dataFromAdditionalColumns),
            $this->equalTo($actualValue)
        );

        $this->valueResolver->assertValue(
            $fieldMapping,
            $context,
            $dataFromAdditionalColumns,
            $actualValue
        );
    }

}
