<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping\ServiceMapping;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\ServiceMapping;

final class ServiceMappingTest extends TestCase
{

    /**
     * @var ServiceMapping
     */
    private $serviceMapping;

    /**
     * @var ServiceMapping
     */
    private $defaultServiceMapping;

    public function setUp()
    {
        $this->serviceMapping = new ServiceMapping(
            "some_service_id",
            true,
            "some origin"
        );

        $this->defaultServiceMapping = new ServiceMapping(
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

}
