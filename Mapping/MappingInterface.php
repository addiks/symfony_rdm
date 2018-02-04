<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping;

use Doctrine\DBAL\Schema\Column;

/**
 * A class that implements this interface indicates that it to be used as mapping-information for this RDM bundle.
 */
interface MappingInterface
{

    /**
     * Returns a human-readable string describing where this mapping was defined.
     *
     * Examples:
     *  - "in field 'foo' of entity 'Lorem\Ipsum'"
     *  - "in file 'foo/bar/baz.orm.xml' on line 123"
     *  - "in the table 'entity_mappings' in row with id 456"
     */
    public function describeOrigin(): string;

    /**
     * Collects metadata about additional columns from this mapping (or it's sub-mappings).
     *
     * @return array<Column>
     */
    public function collectDBALColumns(): array;

}
