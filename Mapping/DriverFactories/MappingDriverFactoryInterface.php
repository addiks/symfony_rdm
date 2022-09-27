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
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;

interface MappingDriverFactoryInterface
{

    /**
     * Tries to create an rdm-metadata-mapping-driver for given doctrine2-metadata-driver.
     * If this factory cannot create a driver for the given driver, it returns NULL.
     */
    public function createRDMMappingDriver(
        MappingDriver $mappingDriver
    ): ?MappingDriverInterface;

}
