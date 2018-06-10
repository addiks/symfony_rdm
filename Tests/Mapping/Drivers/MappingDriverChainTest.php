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
use Addiks\RDMBundle\Tests\Hydration\EntityExample;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use ReflectionProperty;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverChain;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingDriverChainTest extends TestCase
{

    /**
     * @var MappingDriverChain
     */
    private $mappingDriver;

    /**
     * @var MappingDriverInterface
     */
    private $innerDriverA;

    /**
     * @var MappingDriverInterface
     */
    private $innerDriverB;

    public function setUp()
    {
        $this->innerDriverA = $this->createMock(MappingDriverInterface::class);
        $this->innerDriverB = $this->createMock(MappingDriverInterface::class);

        $this->mappingDriver = new MappingDriverChain([
            $this->innerDriverA,
            $this->innerDriverB,
        ]);
    }

    /**
     * @test
     */
    public function shouldCollectMappingData()
    {
        $fieldMappingA = new ServiceMapping($this->createMock(ContainerInterface::class), "some_service");
        $fieldMappingB = new ServiceMapping($this->createMock(ContainerInterface::class), "other_service");

        /** @var array<Service> $expectedAnnotations */
        $expectedFieldMappings = [
            'foo' => $fieldMappingA,
            'bar' => $fieldMappingB
        ];

        $this->innerDriverA->method('loadRDMMetadataForClass')->willReturn(
            new EntityMapping(EntityExample::class, [
                'foo' => $fieldMappingA
            ])
        );

        $this->innerDriverB->method('loadRDMMetadataForClass')->willReturn(
            new EntityMapping(EntityExample::class, [
                'bar' => $fieldMappingB
            ])
        );

        /** @var EntityMapping $actualMapping */
        $actualMapping = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedFieldMappings, $actualMapping->getFieldMappings());
    }

}
