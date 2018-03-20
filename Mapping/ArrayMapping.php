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
use Webmozart\Assert\Assert;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\Mapping\ArrayMappingInterface;

final class ArrayMapping implements ArrayMappingInterface
{

    /**
     * @var array<MappingInterface>
     */
    private $entryMappings = array();

    /**
     * @var string
     */
    private $origin;

    public function __construct(array $entryMappings, string $origin = "unknown")
    {
        $this->origin = $origin;

        foreach ($entryMappings as $key => $entryMapping) {
            /** @var MappingInterface $entryMapping */

            Assert::isInstanceOf($entryMapping, MappingInterface::class);

            $this->entryMappings[$key] = $entryMapping;
        }
    }

    public function getEntryMappings(): array
    {
        return $this->entryMappings;
    }

    public function describeOrigin(): string
    {
        return $this->origin;
    }

    public function collectDBALColumns(): array
    {
        /** @var array<Column> $dbalColumns */
        $dbalColumns = array();

        foreach ($this->entryMappings as $entryMapping) {
            /** @var MappingInterface $entryMapping */

            $dbalColumns = array_merge(
                $dbalColumns,
                $entryMapping->collectDBALColumns()
            );
        }

        return $dbalColumns;
    }

}