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
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Mapping\NullableMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Exception\InvalidMappingException;

final class NullableValueResolver implements ValueResolverInterface
{

    /**
     * @var ValueResolverInterface
     */
    private $rootValueResolver;

    public function __construct(ValueResolverInterface $rootValueResolver)
    {
        $this->rootValueResolver = $rootValueResolver;
    }

    public function resolveValue(
        MappingInterface $fieldMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed|null $value */
        $value = null;

        if ($fieldMapping instanceof NullableMappingInterface) {
            /** @var MappingInterface $innerMapping */
            $innerMapping = $fieldMapping->getInnerMapping();

            /** @var string|null $columnName */
            $columnName = $fieldMapping->getDeterminatorColumnName();

            if (empty($columnName)) {
                /** @var array<Column> $columns */
                $columns = $fieldMapping->collectDBALColumns();

                if (empty($columns)) {
                    throw new InvalidMappingException(sprintf(
                        "Nullable mapping needs at least one column (or subcolumn) in %s!",
                        $fieldMapping->describeOrigin()
                    ));
                }

                $columnName = array_values($columns)[0]->getName();
            }

            if (array_key_exists($columnName, $dataFromAdditionalColumns)
            && $dataFromAdditionalColumns[$columnName]) {
                $value = $this->rootValueResolver->resolveValue(
                    $innerMapping,
                    $context,
                    $dataFromAdditionalColumns
                );
            }
        }

        return $value;
    }

    public function revertValue(
        MappingInterface $fieldMapping,
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<scalar> $data */
        $data = array();

        if ($fieldMapping instanceof NullableMappingInterface && !is_null($valueFromEntityField)) {
            /** @var MappingInterface $innerMapping */
            $innerMapping = $fieldMapping->getInnerMapping();

            /** @var string|null $columnName */
            $columnName = $fieldMapping->getDeterminatorColumnName();

            $data = $this->rootValueResolver->revertValue(
                $innerMapping,
                $context,
                $valueFromEntityField
            );

            if (!empty($columnName) && !array_key_exists($columnName, $data)) {
                $data[$columnName] = true;
            }
        }

        return $data;
    }

    public function assertValue(
        MappingInterface $fieldMapping,
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
    }

}
