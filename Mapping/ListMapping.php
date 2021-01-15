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

use Doctrine\DBAL\Schema\Column;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Addiks\RDMBundle\Exception\FailedRDMAssertionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;

final class ListMapping implements MappingInterface
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

    public function resolveValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns
    ) {
        /** @var null|array<mixed> $value */
        $value = null;

        /** @var string $columnName */
        $columnName = $this->column->getName();

        if (isset($dataFromAdditionalColumns[$columnName])) {
            Assert::string($dataFromAdditionalColumns[$columnName]);

            /** @var array<string> $rawValues */
            $serializedValues = json_decode($dataFromAdditionalColumns[$columnName], true);

            if (is_array($serializedValues)) {
                $value = array();

                foreach ($serializedValues as $key => $entryData) {
                    $value[$key] = $this->entryMapping->resolveValue(
                        $context,
                        [
                            '' => $entryData,
                            $columnName => $entryData
                        ]
                    );
                }
            }
        }

        return $value;
    }

    public function revertValue(
        HydrationContextInterface $context,
        $valueFromEntityField
    ): array {
        /** @var array<string, string> $data */
        $data = array();

        /** @var string $columnName */
        $columnName = $this->column->getName();

        /** @var array<string> $serializedValues */
        $serializedValues = array();

        if (is_iterable($valueFromEntityField)) {
            foreach ($valueFromEntityField as $key => $valueFromEntry) {
                /** @var mixed $valueFromEntry */

                $entryData = $this->entryMapping->revertValue(
                    $context,
                    $valueFromEntry
                );

                if (count($entryData) === 1) {
                    $serializedValues[$key] = array_values($entryData)[0];

                } elseif (!empty($entryData)) {
                    $serializedValues[$key] = $entryData;
                }
            }
        }

        $data[$columnName] = json_encode($serializedValues);

        return $data;
    }

    public function assertValue(
        HydrationContextInterface $context,
        array $dataFromAdditionalColumns,
        $actualValue
    ): void {
        if (!is_array($actualValue) && !is_null($actualValue)) {
            throw FailedRDMAssertionException::expectedArray(
                $actualValue,
                $this->origin
            );
        }
    }

    public function wakeUpMapping(ContainerInterface $container): void
    {
        $this->entryMapping->wakeUpMapping($container);
    }

}
