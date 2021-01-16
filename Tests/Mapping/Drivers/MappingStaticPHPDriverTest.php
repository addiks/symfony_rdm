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
use Addiks\RDMBundle\Mapping\Drivers\MappingStaticPHPDriver;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingStaticPHPDriverTest extends TestCase
{

    /**
     * @var MappingStaticPHPDriver
     */
    private $mappingDriver;

    public function setUp(): void
    {
        $this->mappingDriver = new MappingStaticPHPDriver();
    }

    /**
     * @test
     */
    public function shouldReadMappingData()
    {
        $expectedMapping = new EntityMapping(EntityExample::class, [
            'foo' => new ServiceMapping($this->createMock(ContainerInterface::class), 'some_service'),
            'bar' => new ServiceMapping($this->createMock(ContainerInterface::class), 'other_service')
        ]);

        EntityExample::$staticMetadata = $expectedMapping;

        /** @var EntityMapping $actualMapping */
        $actualMapping = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedMapping, $actualMapping);
    }

}
