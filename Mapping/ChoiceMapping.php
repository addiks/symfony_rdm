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

use Addiks\RDMBundle\Mapping\ChoiceMappingInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\TextType;

final class ChoiceMapping implements ChoiceMappingInterface
{

    /**
     * @var string
     */
    private $determinatorColumnName;

    /**
     * @var array<MappingInterface>
     */
    private $choiceMappings = array();

    /**
     * @var string
     */
    private $originDescription;

    public function __construct(
        string $determinatorColumnName,
        array $choiceMappings,
        string $originDescription = ""
    ) {
        $this->determinatorColumnName = $determinatorColumnName;
        $this->originDescription = $originDescription;

        foreach ($choiceMappings as $determinator => $choiceMapping) {
            /** @var MappingInterface $choiceMapping */

            $this->addChoice($determinator, $choiceMapping);
        }
    }

    public function getChoices(): array
    {
        return $this->choiceMappings;
    }

    public function describeOrigin(): string
    {
        return $this->originDescription;
    }

    public function collectDBALColumns(): array
    {
        /** @var array<Column> $additionalMappings */
        $additionalMappings = array(
            new Column(
                $this->getDeterminatorColumnName(),
                Type::getType("text"),
                [
                    'length' => 255
                ]
            )
        );

        foreach ($this->choiceMappings as $choiceMapping) {
            /** @var MappingInterface $choiceMapping */

            $additionalMappings = array_merge(
                $additionalMappings,
                $choiceMapping->collectDBALColumns()
            );
        }

        return $additionalMappings;
    }

    public function getDeterminatorColumnName(): string
    {
        return $this->determinatorColumnName;
    }

    private function addChoice(string $determinator, MappingInterface $choiceMapping)
    {
        $this->choiceMappings[$determinator] = $choiceMapping;
    }

}
