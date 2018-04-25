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
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Addiks\RDMBundle\Mapping\ListMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;

class ListValueResolver implements ValueResolverInterface
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
        MappingInterface $listMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var null|array<mixed> $value */
        $value = null;

        if ($listMapping instanceof ListMappingInterface) {
            /** @var MappingInterface $entryMapping */
            $entryMapping = $listMapping->getEntryMapping();

            /** @var string $columnName */
            $columnName = $listMapping->getDBALColumn()->getName();

            if (isset($dataFromAdditionalColumns[$columnName])) {
                /** @var array<string> $rawValues */
                $serializedValues = json_decode($dataFromAdditionalColumns[$columnName], true);

                if (is_array($serializedValues)) {
                    $value = array();

                    foreach ($serializedValues as $key => $entryData) {
                        $value[$key] = $this->entryValueResolver->resolveValue(
                            $entryMapping,
                            $context,
                            [
                                '' => $entryData,
                                $columnName => $entryData
                            ]
                        );
                    }
                }
            }
        }

        return $value;
    }

    public function revertValue(
        MappingInterface $listMapping,
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<string, string> $data */
        $data = array();

        if ($listMapping instanceof ListMappingInterface && is_array($valueFromEntityField)) {
            /** @var MappingInterface $entryMapping */
            $entryMapping = $listMapping->getEntryMapping();

            /** @var string $columnName */
            $columnName = $listMapping->getDBALColumn()->getName();

            /** @var array<string> $serializedValues */
            $serializedValues = array();

            foreach ($valueFromEntityField as $key => $valueFromEntry) {
                /** @var mixed $valueFromEntry */

                $entryData = $this->entryValueResolver->revertValue(
                    $entryMapping,
                    $context,
                    $valueFromEntry
                );

                if (count($entryData) === 1) {
                    $serializedValues[$key] = array_values($entryData)[0];

                } elseif (isset($entryData[$columnName])) {
                    $serializedValues[$key] = $entryData;
                }
            }

            $data[$columnName] = json_encode($serializedValues);
        }

        return $data;
    }

    public function assertValue(
        MappingInterface $listMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        if ($listMapping instanceof ListMappingInterface) {
            if (!is_array($actualValue) && !is_null($actualValue)) {
                throw FailedRDMAssertionException::expectedArray(
                    $actualValue,
                    $listMapping->describeOrigin()
                );
            }
        }
    }

}
