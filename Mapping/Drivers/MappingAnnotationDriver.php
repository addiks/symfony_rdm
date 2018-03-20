<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Drivers;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionProperty;
use Addiks\RDMBundle\Mapping\Drivers\MappingDriverInterface;
use Addiks\RDMBundle\Mapping\MappingInterface;
use Addiks\RDMBundle\Mapping\EntityMapping;
use Addiks\RDMBundle\Mapping\Annotation\Service;
use Addiks\RDMBundle\Mapping\ServiceMapping;
use Addiks\RDMBundle\Mapping\EntityMappingInterface;
use Addiks\RDMBundle\Mapping\Annotation\Choice;
use Addiks\RDMBundle\Mapping\ChoiceMapping;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Doctrine\ORM\Mapping\Column as ColumnAnnotation;
use Doctrine\DBAL\Schema\Column as DBALColumn;
use Doctrine\DBAL\Types\Type;
use Addiks\RDMBundle\Mapping\ObjectMapping;
use Addiks\RDMBundle\Mapping\CallDefinitionInterface;
use Addiks\RDMBundle\Mapping\FieldMapping;
use Addiks\RDMBundle\Mapping\Annotation\Obj;
use Addiks\RDMBundle\Mapping\Annotation\Arr;
use Addiks\RDMBundle\Mapping\ArrayMapping;

final class MappingAnnotationDriver implements MappingDriverInterface
{

    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function loadRDMMetadataForClass(string $className): ?EntityMappingInterface
    {
        /** @var ?EntityMappingInterface $mapping */
        $mapping = null;

        /** @var array<MappingInterface> $fieldMappings */
        $fieldMappings = array();

        $classReflection = new ReflectionClass($className);

        foreach ($classReflection->getProperties() as $propertyReflection) {
            /** @var ReflectionProperty $propertyReflection */

            /** @var string $fieldName */
            $fieldName = $propertyReflection->getName();

            /** @var array<object> $annotations */
            $annotations = $this->annotationReader->getPropertyAnnotations($propertyReflection);

            foreach ($annotations as $annotation) {
                /** @var object $annotation */

                $fieldMapping = $this->convertAnnotationToMapping($annotation, $fieldName, $className);

                if ($fieldMapping instanceof MappingInterface) {
                    $fieldMappings[$fieldName] = $fieldMapping;
                }
            }
        }

        if (!empty($fieldMappings)) {
            $mapping = new EntityMapping($className, $fieldMappings);
        }

        return $mapping;
    }

    /**
     * @param object $annotation
     */
    private function convertAnnotationToMapping(
        $annotation,
        string $fieldName,
        string $className,
        bool $convertFieldsOnRootLevel = false
    ): ?MappingInterface {
        /** @var ?MappingInterface $fieldMapping */
        $fieldMapping = null;

        if ($annotation instanceof Service) {
            $fieldMapping = new ServiceMapping(
                $annotation->id,
                $annotation->lax,
                sprintf(
                    "in entity '%s' on field '%s'",
                    $className,
                    $fieldName
                )
            );

        } elseif ($annotation instanceof Choice) {
            /** @var string|null|ColumnAnnotation $column */
            $column = $annotation->column;

            if (!$column instanceof ColumnAnnotation) {
                /** @var string $columnName */
                $columnName = $fieldName;

                if (is_string($column)) {
                    $columnName = $column;
                }

                $column = new ColumnAnnotation();
                $column->name = $columnName;
                $column->type = 'string';
                $column->length = 255;
                $column->precision = 10;
                $column->nullable = (bool)$annotation->nullable;
            }

            $dbalColumn = new DBALColumn(
                $column->name,
                Type::getType($column->type),
                [
                    'notnull'   => !$column->nullable,
                    'length'    => $column->length,
                    'precision' => $column->precision,
                    'scale'     => $column->scale,
                ]
            );

            /** @var array<MappingInterface>*/
            $choiceMappings = array();

            foreach ($annotation->choices as $determinator => $choiceAnnotation) {
                /** @var ?MappingInterface $choiceMapping */
                $choiceMapping = $this->convertAnnotationToMapping(
                    $choiceAnnotation,
                    $fieldName,
                    $className
                );

                if ($choiceMapping instanceof MappingInterface) {
                    $choiceMappings[$determinator] = $choiceMapping;

                } else {
                    throw new InvalidMappingException(sprintf(
                        "Invalid mapping on entity '%s' in field '%s' of choice-option '%s'!",
                        $className,
                        $fieldName,
                        $determinator
                    ));
                }
            }

            $fieldMapping = new ChoiceMapping(
                $dbalColumn,
                $choiceMappings,
                sprintf(
                    "in entity '%s' on field '%s'",
                    $className,
                    $fieldName
                )
            );

        } elseif ($annotation instanceof Obj) {
            /** @var array<MappingInterface> $subFieldMappings */
            $subFieldMappings = array();

            /** @var null|CallDefinitionInterface $factory */
            $factory = null;

            /** @var null|CallDefinitionInterface $serializer */
            $serializer = null;

            foreach ($annotation->fields as $subFieldName => $subFieldAnnotation) {
                /** @var object $fieldAnnotation */

                $subFieldMappings[$subFieldName] = $this->convertAnnotationToMapping(
                    $subFieldAnnotation,
                    $fieldName . "->" . $subFieldName,
                    $className,
                    true
                );
            }

            $fieldMapping = new ObjectMapping(
                $annotation->{"class"},
                $subFieldMappings,
                sprintf(
                    "in entity '%s' on field '%s'",
                    $className,
                    $fieldName
                ),
                $factory,
                $serializer
            );

        } elseif ($annotation instanceof Arr) {
            /** @var array<MappingInterface> $entryMappings */
            $entryMappings = array();

            foreach ($annotation->entries as $key => $entryAnnotaton) {
                $entryMappings[$key] = $this->convertAnnotationToMapping(
                    $entryAnnotaton,
                    $fieldName . "->" . $key,
                    $className,
                    true
                );
            }

            $fieldMapping = new ArrayMapping(
                $entryMappings,
                sprintf(
                    "in entity '%s' on field '%s'",
                    $className,
                    $fieldName
                )
            );

        } elseif ($annotation instanceof ColumnAnnotation && $convertFieldsOnRootLevel) {
            /** @var DBALColumn $dbalColumn */
            $dbalColumn = new DBALColumn(
                $annotation->name,
                Type::getType($annotation->type),
                [
                    'notnull'   => !$annotation->nullable,
                    'length'    => $annotation->length,
                    'precision' => $annotation->precision,
                    'scale'     => $annotation->scale,
                ]
            );

            $fieldMapping = new FieldMapping(
                $dbalColumn,
                sprintf(
                    "in entity '%s' on field '%s'",
                    $className,
                    $fieldName
                )
            );
        }

        return $fieldMapping;
    }

}
