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

final class ArrayValueResolver implements ValueResolverInterface
{

    /**
     * @var ValueResolverInterface
     */
    private $entryValueResolver;

    public function __construct(ValueResolverInterface $entryValueResolver)
    {
        $this->entryValueResolver = $entryValueResolver;
    }

    public function resolveValue(
        MappingInterface $arrayMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var null|array<mixed> $value */
        $value = null;

        if ($arrayMapping instanceof ArrayMappingInterface) {
            $value = array();

            foreach ($arrayMapping->getEntryMappings() as $key => $entryMapping) {
                /** @var MappingInterface $entryMapping */

                $value[$key] = $this->entryValueResolver->resolveValue(
                    $entryMapping,
                    $context,
                    $dataFromAdditionalColumns
                );
            }
        }

        return $value;
    }

    public function revertValue(
        MappingInterface $arrayMapping,
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<string, string> $data */
        $data = array();

        if ($arrayMapping instanceof ArrayMappingInterface && is_array($valueFromEntityField)) {
            foreach ($arrayMapping->getEntryMappings() as $key => $entryMapping) {
                /** @var MappingInterface $entryMapping */

                /** @var mixed $valueFromEntry */
                $valueFromEntry = null;

                if (isset($valueFromEntityField[$key])) {
                    $valueFromEntry = $valueFromEntityField[$key];
                }

                $data = array_merge(
                    $data,
                    $this->entryValueResolver->revertValue(
                        $entryMapping,
                        $context,
                        $valueFromEntry
                    )
                );
            }
        }

        return $data;
    }

    public function assertValue(
        MappingInterface $arrayMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        if ($arrayMapping instanceof ArrayMappingInterface) {
            if (!is_array($actualValue) && !is_null($actualValue)) {
                throw FailedRDMAssertionException::expectedArray(
                    $actualValue,
                    $arrayMapping->describeOrigin()
                );
            }
        }
    }

}
