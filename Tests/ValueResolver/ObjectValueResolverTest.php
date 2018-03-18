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
use Addiks\RDMBundle\ValueResolver\ObjectValueResolver;
use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Tests\ValueObjectExample;
use Addiks\RDMBundle\Mapping\ObjectMappingInterface;
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\ValueResolver\CallDefinitionExecuterInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionExceptionInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class ObjectValueResolverTest extends TestCase
{

    /**
     * @var ObjectValueResolver
     */
    private $valueResolver;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ValueResolverInterface
     */
    private $fieldValueResolver;

    /**
     * @var CallDefinitionExecuterInterface
     */
    private $callDefinitionExecuter;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->fieldValueResolver = $this->createMock(ValueResolverInterface::class);
        $this->callDefinitionExecuter = $this->createMock(CallDefinitionExecuterInterface::class);

        $this->valueResolver = new ObjectValueResolver(
            $this->container,
            $this->fieldValueResolver,
            $this->callDefinitionExecuter
        );
    }

    /**
     * @test
     */
    public function shouldResolveValue()
    {
        /** @var ObjectMappingInterface $objectMapping */
        $objectMapping = $this->createMock(ObjectMappingInterface::class);

        /** @var MappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(MappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        /** @var mixed $dataFromAdditionalColumns */
        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet',
        );

        $objectMapping->method('getClassName')->willReturn(ValueObjectExample::class);
        $objectMapping->method('getFieldMappings')->willReturn([
            'amet' => $fieldMapping
        ]);

        $this->fieldValueResolver->method('resolveValue')->will($this->returnValueMap([
            [$fieldMapping, $entity, $dataFromAdditionalColumns, "FOO BAR BAZ"],
        ]));

        /** @var mixed $actualObject */
        $actualObject = $this->valueResolver->resolveValue($objectMapping, $entity, $dataFromAdditionalColumns);

        $this->assertTrue($actualObject instanceof ValueObjectExample);
        $this->assertEquals("FOO BAR BAZ", $actualObject->getAmet());
    }

    /**
     * @test
     */
    public function shouldRevertValue()
    {
        /** @var ObjectMappingInterface $objectMapping */
        $objectMapping = $this->createMock(ObjectMappingInterface::class);

        /** @var MappingInterface $fieldMapping */
        $fieldMapping = $this->createMock(MappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        $actualObject = new ValueObjectExample("foo");
        $actualObject->setAmet('sit amet');

        $objectMapping->method('getClassName')->willReturn(ValueObjectExample::class);
        $objectMapping->method('getFieldMappings')->willReturn([
            'amet' => $fieldMapping
        ]);

        $this->fieldValueResolver->method('revertValue')->will($this->returnValueMap([
            [$fieldMapping, $entity, 'sit amet', ['amet' => "FOO BAR BAZ"]],
        ]));

        /** @var array $actualData */
        $actualData = $this->valueResolver->revertValue($objectMapping, $entity, $actualObject);

        $this->assertEquals(['amet' => "FOO BAR BAZ"], $actualData);
    }

    /**
     * @test
     */
    public function shouldFailAssertionOnWrongObject()
    {
        /** @var ObjectMappingInterface $objectMapping */
        $objectMapping = $this->createMock(ObjectMappingInterface::class);

        /** @var EntityExample $entity */
        $entity = $this->createMock(EntityExample::class);

        $dataFromAdditionalColumns = array(
            'lorem' => 'ipsum',
            'dolor' => 'sit amet',
        );

        /** @var mixed $actualValue */
        $actualValue = $this->createMock(EntityExample::class);

        $objectMapping->method('getClassName')->willReturn(ValueObjectExample::class);

        $this->expectException(FailedRDMAssertionExceptionInterface::class);

        $this->valueResolver->assertValue($objectMapping, $entity, $dataFromAdditionalColumns, $actualValue);
    }

}
