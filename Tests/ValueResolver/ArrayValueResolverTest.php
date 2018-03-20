<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\ValueResolver\ArrayValueResolver;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\ArrayMappingInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;

final class ArrayValueResolverTest extends TestCase
{

    /**
     * @var ArrayValueResolver
     */
    private $arrayValueResolver;

    /**
     * @var ValueResolverInterface
     */
    private $entryValueResolver;

    public function setUp()
    {
        $this->entryValueResolver = $this->createMock(ValueResolverInterface::class);

        $this->arrayValueResolver = new ArrayValueResolver($this->entryValueResolver);
    }

    /**
     * @test
     */
    public function shouldResolveValue()
    {
        /** @var ArrayMappingInterface $arrayMapping */
        $arrayMapping = $this->createMock(ArrayMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var MappingInterface $fooMapping */
        $fooMapping = $this->createMock(MappingInterface::class);

        /** @var MappingInterface $bazMapping */
        $bazMapping = $this->createMock(MappingInterface::class);

        /** @var mixed $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit',
        );

        /** @var array<mixed> $expectedResult */
        $expectedResult = [
            'foo' => 'bar',
            'baz' => 3.1415
        ];

        $arrayMapping->method('getEntryMappings')->willReturn([
            'foo' => $fooMapping,
            'baz' => $bazMapping,
        ]);

        $this->entryValueResolver->method('resolveValue')->will($this->returnValueMap([
            [$fooMapping, $entity, $dataFromAdditionalColumns, 'bar'],
            [$bazMapping, $entity, $dataFromAdditionalColumns, 3.1415],
        ]));

        /** @var mixed $actualResult */
        $actualResult = $this->arrayValueResolver->resolveValue($arrayMapping, $entity, $dataFromAdditionalColumns);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldRevertValue()
    {
        /** @var ArrayMappingInterface $arrayMapping */
        $arrayMapping = $this->createMock(ArrayMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var MappingInterface $fooMapping */
        $fooMapping = $this->createMock(MappingInterface::class);

        /** @var MappingInterface $bazMapping */
        $bazMapping = $this->createMock(MappingInterface::class);

        /** @var mixed $valueFromEntityField */
        $valueFromEntityField = array(
            'foo' => 'bar',
            'baz' => 3.1415
        );

        /** @var array<string, string> $expectedResult */
        $expectedResult = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit',
        );

        $arrayMapping->method('getEntryMappings')->willReturn([
            'foo' => $fooMapping,
            'baz' => $bazMapping,
        ]);

        $this->entryValueResolver->method('revertValue')->will($this->returnValueMap([
            [$fooMapping, $entity, 'bar', ['lorem' => 'ipsum']],
            [$bazMapping, $entity, 3.1415, ['dolor' => 'sit']],
        ]));

        /** @var mixed $actualResult */
        $actualResult = $this->arrayValueResolver->revertValue($arrayMapping, $entity, $valueFromEntityField);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function shouldNotRevertForNonArray()
    {
        /** @var ArrayMappingInterface $arrayMapping */
        $arrayMapping = $this->createMock(ArrayMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var MappingInterface $fooMapping */
        $fooMapping = $this->createMock(MappingInterface::class);

        /** @var MappingInterface $bazMapping */
        $bazMapping = $this->createMock(MappingInterface::class);

        $arrayMapping->method('getEntryMappings')->willReturn([
            'foo' => $fooMapping,
            'baz' => $bazMapping,
        ]);

        $this->entryValueResolver->method('revertValue')->will($this->returnValueMap([
            [$fooMapping, $entity, 'bar', ['lorem' => 'ipsum']],
            [$bazMapping, $entity, 3.1415, ['dolor' => 'sit']],
        ]));

        /** @var mixed $actualResult */
        $actualResult = $this->arrayValueResolver->revertValue($arrayMapping, $entity, "a non-array");

        $this->assertEquals([], $actualResult);
    }

    /**
     * @test
     */
    public function shouldAssertValue()
    {
        $this->expectException(FailedRDMAssertionExceptionInterface::class);

        /** @var ArrayMappingInterface $arrayMapping */
        $arrayMapping = $this->createMock(ArrayMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        $this->arrayValueResolver->assertValue($arrayMapping, $entity, [], "foo");
    }

}
