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

final class MappingDriverChain implements MappingDriverInterface
{

    /**
     * @var array<MappingDriverInterface>
     */
    private $innerDrivers = array();

    public function __construct(array $innerDrivers = array())
    {
        foreach ($innerDrivers as $innerDriver) {
            /** @var MappingDriverInterface $innerDriver */

            $this->addInnerMetadataDriver($innerDriver);
        }
    }

    public function loadRDMMetadataForClass($className): array
    {
        /** @var mixed $serviceAnnotations */
        $serviceAnnotations = array();

        foreach ($this->innerDrivers as $innerDriver) {
            /** @var MappingDriverInterface $innerDriver */

            $driverServiceAnnotations = $innerDriver->loadRDMMetadataForClass($className);

            $serviceAnnotations = array_merge($serviceAnnotations, $driverServiceAnnotations);
        }

        return $serviceAnnotations;
    }

    private function addInnerMetadataDriver(MappingDriverInterface $innerDriver)
    {
        $this->innerDrivers[] = $innerDriver;
    }

}
