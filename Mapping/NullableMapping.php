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
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\NullableMappingInterface;

final class NullableMapping implements NullableMappingInterface
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

}
