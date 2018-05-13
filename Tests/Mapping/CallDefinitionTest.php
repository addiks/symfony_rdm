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
use Addiks\RDMBundle\Mapping\CallDefinition;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class CallDefinitionTest extends TestCase
{

    /**
     * @var CallDefinition
     */
    private $callDefinition;

    public function setUp()
    {
        $this->callDefinition = new CallDefinition(
            "someRoutineName",
            "someObjectReference",
            [
                $this->createMock(MappingInterface::class),
                $this->createMock(MappingInterface::class),
            ],
            true
        );
    }

    /**
     * @test
     */
    public function shouldStoreObjectReference()
    {
        $this->assertEquals("someObjectReference", $this->callDefinition->getObjectReference());
    }

    /**
     * @test
     */
    public function shouldStoreRoutineName()
    {
        $this->assertEquals("someRoutineName", $this->callDefinition->getRoutineName());
    }

    /**
     * @test
     */
    public function shouldKnowIfStatic()
    {
        $this->assertEquals(true, $this->callDefinition->isStaticCall());
    }

    /**
     * @test
     */
    public function shouldStoreArgumentMapping()
    {
        $this->assertEquals([
            $this->createMock(MappingInterface::class),
            $this->createMock(MappingInterface::class),
        ], $this->callDefinition->getArgumentMappings());
    }

}
