<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\DriverFactories;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
use Addiks\RDMBundle\Mapping\DriverFactories\MappingDriverFactoryInterface;
use Addiks\RDMBundle\Mapping\Drivers\MappingStaticPHPDriver;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;

final class MappingStaticPHPDriverFactory implements MappingDriverFactoryInterface
{

    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface {
        /** @var ?MappingDriverInterface $rdmMetadataDriver */
        $rdmMetadataDriver = null;

        if ($mappingDriver instanceof StaticPHPDriver) {
            $rdmMetadataDriver = new MappingStaticPHPDriver();
        }

        return $rdmMetadataDriver;
    }

}
