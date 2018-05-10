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
use Addiks\RDMBundle\ValueResolver\NullValueResolver;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;

final class NullValueResolverTest extends TestCase
{

    /**
     * @var NullValueResolver
     */
    private $valueResolver;

    public function setUp()
    {
        $this->valueResolver = new NullValueResolver();
    }

    /**
     * @test
     */
    public function shouldNotResolveValue()
    {
        $this->assertNull($this->valueResolver->resolveValue(
            $this->createMock(MappingInterface::class),
            $this->createMock(HydrationContextInterface::class),
            []
        ));
    }

    /**
     * @test
     */
    public function shouldNotRevertValue()
    {
        $this->assertEmpty($this->valueResolver->revertValue(
            $this->createMock(MappingInterface::class),
            $this->createMock(HydrationContextInterface::class),
            null
        ));
    }

    /**
     * @test
     */
    public function shouldNotAssertValue()
    {
        $this->assertNull($this->valueResolver->assertValue(
            $this->createMock(MappingInterface::class),
            $this->createMock(HydrationContextInterface::class),
            [],
            null
        ));
    }

}
