<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping;

use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;

final class EntityMapping implements EntityMappingInterface
{

    /**
     * @var string
     */
    private $className;

    /**
     * @var array<MappingInterface>
     */
    private $fieldMappings;

    public function __construct(string $className, array $fieldMappings)
    {
        $this->className = $className;

        foreach ($fieldMappings as $fieldName => $fieldMapping) {
            /** @var MappingInterface $fieldMapping */

            $this->addFieldMapping($fieldName, $fieldMapping);
        }
    }

    public function getEntityClassName(): string
    {
        return $this->className;
    }

    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    private function addFieldMapping(string $fieldName, MappingInterface $fieldMapping)
    {
        $this->fieldMappings[$fieldName] = $fieldMapping;
    }

}
