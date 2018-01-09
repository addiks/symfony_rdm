<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Mapping\DriverFactories;

use PHPUnit\Framework\TestCase;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingAnnotationDriverFactory;
use Addiks\RDMBundle\Mapping\Drivers\MappingAnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\Reader;

final class MappingAnnotationDriverFactoryTest extends TestCase
{

    /**
     * @var MappingAnnotationDriverFactory
     */
    private $driverFactory;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function setUp()
    {
        $this->annotationReader = $this->createMock(Reader::class);

        $this->driverFactory = new MappingAnnotationDriverFactory(
            $this->annotationReader
        );
    }

    /**
     * @test
     */
    public function shouldCreatedAnnotationMappingDriver()
    {
        /** @var AnnotationDriver $mappingDriver */
        $mappingDriver = $this->createMock(AnnotationDriver::class);

        /** @var MappingAnnotationDriver $rdmMappingDriver */
        $rdmMappingDriver = $this->driverFactory->createRDMMappingDriver($mappingDriver);

        $this->assertInstanceOf(MappingAnnotationDriver::class, $rdmMappingDriver);
    }

}
