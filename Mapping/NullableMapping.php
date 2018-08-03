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

namespace Addiks\RDMBundle\Mapping;

use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Doctrine\DBAL\Schema\Column;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class NullableMapping implements MappingInterface
{

    /**
     * @var MappingInterface
     */
    private $innerMapping;

    /**
     * @var Column|null
     */
    private $dbalColumn;

    /**
     * @var string
     */
    private $origin;

    public function __construct(
        MappingInterface $innerMapping,
        Column $dbalColumn = null,
        string $origin = "undefined"
    ) {
        $this->innerMapping = $innerMapping;
        $this->dbalColumn = $dbalColumn;
        $this->origin = $origin;
    }

    public function getDBALColumn(): ?Column
    {
        return $this->dbalColumn;
    }

    public function getInnerMapping(): MappingInterface
    {
        return $this->innerMapping;
    }

    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        /** @var array<Column> $dbalColumns */
        $dbalColumns = array();

        foreach ($this->innerMapping->collectDBALColumns() as $dbalColumn) {
            /** @var Column $dbalColumn */

            $dbalColumn = clone $dbalColumn;
            $dbalColumn->setNotnull(false);

            $dbalColumns[] = $dbalColumn;
        }

        if ($this->dbalColumn instanceof Column) {
            $dbalColumns[] = $this->dbalColumn;
        }

        return $dbalColumns;
    }

    public function getDeterminatorColumnName(): ?string
    {
        /** @var string|null $columnName */
        $columnName = null;

        if ($this->dbalColumn instanceof Column) {
            $columnName = $this->dbalColumn->getName();
        }

        return $columnName;
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed|null $value */
        $value = null;

        /** @var string|null $columnName */
        $columnName = $this->getDeterminatorColumnName();

        if (empty($columnName)) {
            /** @var array<Column> $columns */
            $columns = $this->collectDBALColumns();

            if (empty($columns)) {
                throw new InvalidMappingException(sprintf(
                    "Nullable mapping needs at least one column (or subcolumn) in %s!",
                    $this->origin
                ));
            }

            $columnName = array_values($columns)[0]->getName();
        }

        if (array_key_exists($columnName, $dataFromAdditionalColumns)
        && $dataFromAdditionalColumns[$columnName]) {
            $value = $this->innerMapping->resolveValue(
                $context,
                $dataFromAdditionalColumns
            );
        }

        return $value;
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<scalar> $data */
        $data = array();

        /** @var string|null $columnName */
        $columnName = $this->getDeterminatorColumnName();

        if (!is_null($valueFromEntityField)) {
            $data = $this->innerMapping->revertValue(
                $context,
                $valueFromEntityField
            );

            if (!empty($columnName) && !array_key_exists($columnName, $data)) {
                $data[$columnName] = true;
            }

        } elseif (!empty($columnName)) {
            $data[$columnName] = false;
        }

        return $data;
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
        $this->innerMapping->wakeUpMapping($container);
    }

}
