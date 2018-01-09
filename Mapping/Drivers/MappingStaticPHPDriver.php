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

final class MappingStaticPHPDriver implements MappingDriverInterface
{

    public function loadRDMMetadataForClass($className): array
    {
        /** @var array<Service> $services */
        $services = array();

        if (method_exists($className, 'loadRDMMetadata')) {
            foreach ($className::loadRDMMetadata() as $serviceCandidate) {
                /** @var mixed $serviceCandidate */

                if ($serviceCandidate instanceof Service) {
                    $services[] = $serviceCandidate;
                }
            }
        }

        return $services;
    }

}
