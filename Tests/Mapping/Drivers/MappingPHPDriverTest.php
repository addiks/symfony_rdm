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
use Addiks\RDMBundle\Mapping\Drivers\MappingPHPDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;

final class MappingPHPDriverTest extends TestCase
{

    /**
     * @var MappingPHPDriver
     */
    private $mappingDriver;

    /**
     * @var MappingDriverInterface
     */
    private $fileLocator;

    public function setUp()
    {
        $this->fileLocator = $this->createMock(FileLocator::class);

        $this->mappingDriver = new MappingPHPDriver($this->fileLocator);
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

        # The mock-file just returns this global-variable.
        $GLOBALS['addiks_symfony_rdm_tests_mapping_driver_php_annotations'] = $expectedAnnotations;

        $this->fileLocator->method('findMappingFile')->willReturn(__DIR__ . "/phpMappingMock.php");

        /** @var array<Service> $actualAnnotations */
        $actualAnnotations = $this->mappingDriver->loadRDMMetadataForClass(EntityExample::class);

        $this->assertEquals($expectedAnnotations, $actualAnnotations);
    }

}
