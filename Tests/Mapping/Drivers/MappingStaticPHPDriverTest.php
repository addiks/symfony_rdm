<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
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
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingStaticPHPDriver;

final class MappingStaticPHPDriverTest extends TestCase
{

    /**
     * @var MappingStaticPHPDriver
     */
    private $mappingDriver;

    public function setUp()
    {
        $this->mappingDriver = new MappingStaticPHPDriver();
    }

    /**
     * @test
     */
    public function shouldReadMappingData()
    {
        $someAnnotationA = new Service();
        $someAnnotationA->id = "some_service";
        $someAnnotationA->field = "foo";

        $someAnnotationB = new Service();
        $someAnnotationB->id = "other_service";
        $someAnnotationB->field = "bar";

        /** @var array<Service> $expectedAnnotations */
        $expectedAnnotations = [
            $someAnnotationA,
            $someAnnotationB
        ];

        EntityExample::$staticMetadata = $expectedAnnotations;

        /** @var array<Service> $actualAnnotations */
        $actualAnnotations = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedAnnotations, $actualAnnotations);
    }

}
