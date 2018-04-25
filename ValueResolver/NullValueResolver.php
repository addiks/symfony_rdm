<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\ValueResolver;

use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\ArrayMappingInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;

final class NullValueResolver implements ValueResolverInterface
{

    public function resolveValue(
        MappingInterface $arrayMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        return null;
    }

    public function revertValue(
        MappingInterface $arrayMapping,
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        return array();
    }

    public function assertValue(
        MappingInterface $arrayMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
    }

}
