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

use Addiks\RDMBundle\Mapping\ListMappingInterface;
use Doctrine\DBAL\Schema\Column;
use Webmozart\Assert\Assert;

final class ListMapping implements ListMappingInterface
{

    /**
     * @var Column
     */
    private $column;

    /**
     * @var MappingInterface
     */
    private $entryMapping;

    /**
     * @var string
     */
    private $origin;

    public function __construct(
        Column $column,
        MappingInterface $entryMapping,
        string $origin = "unknown"
    ) {
        $this->column = $column;
        $this->entryMapping = $entryMapping;
        $this->origin = $origin;
    }

    public function getDBALColumn(): Column
    {
        return $this->column;
    }

    public function getEntryMapping(): MappingInterface
    {
        return $this->entryMapping;
    }

    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        /** @var array<Column> $dbalColumns */
        $dbalColumns = [$this->column];

        return $dbalColumns;
    }

}
