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

use Addiks\RDMBundle\Mapping\MappingInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ChoiceMapping implements MappingInterface
{

    /**
     * @var Column
     */
    private $determinatorColumn;

    /**
     * @var array<MappingInterface>
     */
    private $choiceMappings = array();

    /**
     * @var string
     */
    private $originDescription;

    /**
     * @param string|Column $determinatorColumn
     */
    public function __construct(
        $determinatorColumn,
        array $choiceMappings,
        string $originDescription = ""
    ) {
        if (!$determinatorColumn instanceof Column) {
            $determinatorColumn = new Column(
                (string)$determinatorColumn,
                Type::getType('string'),
                [
                    'notnull' => false,
                    'length'  => 255,
                ]
            );
        }

        $this->determinatorColumn = clone $determinatorColumn;
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
            clone $this->determinatorColumn
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

    public function getDeterminatorColumn(): Column
    {
        return clone $this->determinatorColumn;
    }

    public function getDeterminatorColumnName(): string
    {
        return $this->determinatorColumn->getName();
    }

    private function addChoice(string $determinator, MappingInterface $choiceMapping): void
    {
        $this->choiceMappings[$determinator] = $choiceMapping;
    }

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $value */
        $value = null;

        /** @var string $determinatorColumn */
        $determinatorColumn = $this->determinatorColumn->getName();

        if (array_key_exists($determinatorColumn, $dataFromAdditionalColumns)) {
            /** @var string|int $determinatorValue */
            $determinatorValue = $dataFromAdditionalColumns[$determinatorColumn];

            if (!empty($determinatorValue) && !array_key_exists($determinatorValue, $this->choiceMappings)) {
                throw new InvalidMappingException(sprintf(
                    "Invalid option-value '%s' for choice-column '%s' on entity %s!",
                    $determinatorValue,
                    $determinatorColumn,
                    $context->getEntityClass()
                ));
            }

            if (isset($this->choiceMappings[$determinatorValue])) {
                $choiceMapping = $this->choiceMappings[$determinatorValue];

                $value = $choiceMapping->resolveValue(
                    $context,
                    $dataFromAdditionalColumns
                );
            }
        }

        return $value;
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<scalar> $data */
        $data = array();

        /** @var string $determinatorColumn */
        $determinatorColumn = $this->determinatorColumn->getName();

        /** @var ?scalar $determinatorValue */
        $determinatorValue = null;

        foreach ($this->choiceMappings as $choiceDeterminatorValue => $choiceMapping) {
            /** @var MappingInterface $choiceMapping */

            $choiceValue = $choiceMapping->resolveValue(
                $context,
                [] # <= I'm not sure how this parameter should be handled correctly in the future,
                   #    but with the current supported features it *should* be irrelevant.
            );

            if ($choiceValue === $valueFromEntityField) {
                $determinatorValue = $choiceDeterminatorValue;
                break;
            }
        }

        $data[$determinatorColumn] = $determinatorValue;

        return $data;
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        /** @var string $determinatorColumn */
        $determinatorColumn = $this->determinatorColumn->getName();

        if (array_key_exists($determinatorColumn, $dataFromAdditionalColumns)) {
            /** @var string|int $determinatorValue */
            $determinatorValue = $dataFromAdditionalColumns[$determinatorColumn];

            if (!empty($determinatorValue) && !array_key_exists($determinatorValue, $this->choiceMappings)) {
                throw new InvalidMappingException(sprintf(
                    "Invalid option-value '%s' for choice-column '%s' on entity %s!",
                    $determinatorValue,
                    $determinatorColumn,
                    $context->getEntityClass()
                ));
            }

            if (isset($this->choiceMappings[$determinatorValue])) {
                $choiceMapping = $this->choiceMappings[$determinatorValue];

                $choiceMapping->assertValue(
                    $context,
                    $dataFromAdditionalColumns,
                    $actualValue
                );
            }
        }
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
        foreach ($this->choiceMappings as $choiceMapping) {
            /** @var MappingInterface $choiceMapping */

            $choiceMapping->wakeUpMapping($container);
        }
    }

}
