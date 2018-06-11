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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Schema\Column;

final class MappingProxy implements MappingInterface
{

    /**
     * @var MappingInterface
     */
    private $innerMapping;

    /**
     * @var string
     */
    private $columnPrefix;

    public function __construct(
        MappingInterface $innerMapping,
        string $columnPrefix
    ) {
        $this->innerMapping = $innerMapping;
        $this->columnPrefix = $columnPrefix;
    }

    public function describeOrigin(): string
    {
        return $this->innerMapping->describeOrigin();
    }

    public function collectDBALColumns(): array
    {
        /** @var array<Column> $dbalColumns */
        $dbalColumns = $this->innerMapping->collectDBALColumns();

        /** @var array<Column> $prefixedColumns */
        $prefixedColumns = array();

        foreach ($dbalColumns as $key => $column) {
            /** @var Column $column */

            $prefixedColumns[$key] = new Column(
                sprintf("%s%s", $this->columnPrefix, $column->getName()),
                $column->getType(),
                $column->toArray()
            );
        }

        return $prefixedColumns;
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        return $this->innerMapping->resolveValue($context, $dataFromAdditionalColumns);
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        return $this->innerMapping->revertValue($context, $valueFromEntityField);
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        $this->innerMapping->assertValue($context, $dataFromAdditionalColumns, $actualValue);
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
        $this->innerMapping->wakeUpMapping($container);
    }

}
