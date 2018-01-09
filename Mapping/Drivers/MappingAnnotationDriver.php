<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Drivers;

use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionProperty;

final class MappingAnnotationDriver implements MappingDriverInterface
{

    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function loadRDMMetadataForClass($className): array
    {
        /** @var array<Service> $services */
        $services = array();

        $classReflection = new ReflectionClass($className);

        foreach ($classReflection->getProperties() as $propertyReflection) {
            /** @var ReflectionProperty $propertyReflection */

            /** @var array<object> $annotations */
            $annotations = $this->annotationReader->getPropertyAnnotations($propertyReflection);

            foreach ($annotations as $annotation) {
                /** @var object $annotation */

                if ($annotation instanceof Service) {
                    $annotation->field = $propertyReflection->getName();

                    $services[] = $annotation;
                }
            }
        }

        return $services;
    }

}
