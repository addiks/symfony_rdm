<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\ValueResolver;

use Addiks\RDMBundle\ValueResolver\ValueResolverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\ChoiceMappingInterface;
use Addiks\RDMBundle\Exception\InvalidMappingException;

final class ChoiceValueResolver implements ValueResolverInterface
{

    /**
     * @var ValueResolverInterface
     */
    private $innerValueResolver;

    public function __construct(
        ValueResolverInterface $innerValueResolver
    ) {
        $this->innerValueResolver = $innerValueResolver;
    }

    public function resolveValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns
    ) {
        /** @var mixed $value */
        $value = null;

        if ($fieldMapping instanceof ChoiceMappingInterface) {
            /** @var string $determinatorColumn */
            $determinatorColumn = $fieldMapping->getDeterminatorColumnName();

            /** @var array<MappingInterface> $choices */
            $choices = $fieldMapping->getChoices();

            if (array_key_exists($determinatorColumn, $dataFromAdditionalColumns)) {
                /** @var scalar $determinatorValue */
                $determinatorValue = $dataFromAdditionalColumns[$determinatorColumn];

                if (!empty($determinatorValue) && !array_key_exists($determinatorValue, $choices)) {
                    throw new InvalidMappingException(sprintf(
                        "Invalid option-value '%s' for choice-column '%s' on entity %s!",
                        $determinatorValue,
                        $determinatorColumn,
                        get_class($entity)
                    ));
                }

                if (isset($choices[$determinatorValue])) {
                    $choiceMapping = $choices[$determinatorValue];

                    $value = $this->innerValueResolver->resolveValue(
                        $choiceMapping,
                        $entity,
                        $dataFromAdditionalColumns
                    );
                }
            }
        }

        return $value;
    }

    public function revertValue(
        MappingInterface $fieldMapping,
        $entity,
        $valueFromEntityField
    ): array {
        /** @var array<scalar> $data */
        $data = null;

        if ($fieldMapping instanceof ChoiceMappingInterface) {
            /** @var string $determinatorColumn */
            $determinatorColumn = $fieldMapping->getDeterminatorColumnName();

            /** @var array<MappingInterface> $choices */
            $choices = $fieldMapping->getChoices();

            /** @var ?scalar $determinatorValue */
            $determinatorValue = null;

            foreach ($choices as $choiceDeterminatorValue => $choiceMapping) {
                /** @var MappingInterface $choiceMapping */

                $choiceValue = $this->innerValueResolver->resolveValue(
                    $choiceMapping,
                    $entity,
                    [] # <= I'm not sure how this parameter should be handled correctly in the future,
                       #    but with the current supported features it *should* be irrelevant.
                );

                if ($choiceValue === $valueFromEntityField) {
                    $determinatorValue = $choiceDeterminatorValue;
                    break;
                }
            }

            $data[$determinatorColumn] = $determinatorValue;
        }

        return $data;
    }

    public function assertValue(
        MappingInterface $fieldMapping,
        $entity,
        array $dataFromAdditionalColumns,
        $actualValue
    ) {
        if ($fieldMapping instanceof ChoiceMappingInterface) {
            /** @var string $determinatorColumn */
            $determinatorColumn = $fieldMapping->getDeterminatorColumnName();

            if (array_key_exists($determinatorColumn, $dataFromAdditionalColumns)) {
                /** @var scalar $determinatorValue */
                $determinatorValue = $dataFromAdditionalColumns[$determinatorColumn];

                /** @var array<MappingInterface> $choices */
                $choices = $fieldMapping->getChoices();

                if (!empty($determinatorValue) && !array_key_exists($determinatorValue, $choices)) {
                    throw new InvalidMappingException(sprintf(
                        "Invalid option-value '%s' for choice-column '%s' on entity %s!",
                        $determinatorValue,
                        $determinatorColumn,
                        get_class($entity)
                    ));
                }

                if (isset($choices[$determinatorValue])) {
                    $choiceMapping = $choices[$determinatorValue];

                    $this->innerValueResolver->assertValue(
                        $choiceMapping,
                        $entity,
                        $dataFromAdditionalColumns,
                        $actualValue
                    );
                }
            }
        }
    }

}
