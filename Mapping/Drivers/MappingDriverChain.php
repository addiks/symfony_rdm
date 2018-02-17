<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Drivers;

use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\EntityMapping;

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

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        /** @var ?EntityMappingInterface $mapping */
        $mapping = null;

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = array();

        foreach ($this->innerDrivers as $innerDriver) {
            /** @var MappingDriverInterface $innerDriver */

            /** @var ?EntityMappingInterface $driverMapping */
            $driverMapping = $innerDriver->loadRDMMetadataForClass($className);

            if ($driverMapping instanceof EntityMappingInterface) {
                $fieldMappings = array_merge($fieldMappings, $driverMapping->getFieldMappings());
            }
        }

        if (!empty($fieldMappings)) {
            $mapping = new EntityMapping($className, $fieldMappings);
        }

        return $mapping;
    }

    private function addInnerMetadataDriver(MappingDriverInterface $innerDriver): void
    {
        $this->innerDrivers[] = $innerDriver;
    }

}
