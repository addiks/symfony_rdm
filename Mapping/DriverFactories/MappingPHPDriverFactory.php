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
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Persistence\Mapping\Driver\FileLocator;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingPHPDriver;

final class MappingPHPDriverFactory implements MappingDriverFactoryInterface
{

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        /** @var ?MappingDriverInterface $rdmMetadataDriver */
        $rdmMetadataDriver = null;

        if ($mappingDriver instanceof PHPDriver) {
            /** @var FileLocator $fileLocator */
            $fileLocator = $mappingDriver->getLocator();

            $rdmMetadataDriver = new MappingPHPDriver($fileLocator);
        }

        return $rdmMetadataDriver;
    }

}
