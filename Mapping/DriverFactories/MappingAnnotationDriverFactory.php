<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\DriverFactories;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingAnnotationDriver;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MappingAnnotationDriverFactory implements MappingDriverFactoryInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(
        ContainerInterface $container,
        Reader $annotationReader
    ) {
        $this->container = $container;
        $this->annotationReader = $annotationReader;
    }

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        /** @var ?MappingDriverInterface $rdmMappingDriver */
        $rdmMappingDriver = null;

        if ($mappingDriver instanceof AnnotationDriver) {
            $rdmMappingDriver = new MappingAnnotationDriver(
                $this->container,
                $this->annotationReader
            );
        }

        return $rdmMappingDriver;
    }

}
