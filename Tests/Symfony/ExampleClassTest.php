<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Symfony;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Symfony\ExampleClass;

final class ExampleClassTest extends TestCase
{

    /**
     * @var ExampleClass
     */
    private $example;

    public function setUp()
    {
        $this->example = new ExampleClass("lorem ipsum", 31415);
    }

    /**
     * @test
     */
    public function shouldHaveFoo()
    {
        $this->assertEquals("lorem ipsum", $this->example->getFoo());
    }

    /**
     * @test
     */
    public function shouldHaveBar()
    {
        $this->assertEquals(31415, $this->example->getBar());
    }

}
