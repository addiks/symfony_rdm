<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\DataLoader\BlackMagic;

use ReflectionClass;
use ReflectionType;
use ReflectionProperty;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Schema\Column;
use Addiks\RDMBundle\DataLoader\BlackMagic\BlackMagicDataLoader;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionObject;
use Webmozart\Assert\Assert;

/** @see BlackMagicDataLoader */
final class BlackMagicColumnReflectionPropertyMock extends ReflectionProperty
{
    private EntityManagerInterface $entityManager;

    private ClassMetadata $classMetadata;

    private ReflectionClass $classMetadataReflection;

    private Column $column;

    private string $fieldName;

    private BlackMagicDataLoader $dataLoader;

    public function __construct(
        EntityManagerInterface $entityManager,
        ClassMetadata $classMetadata,
        Column $column,
        string $fieldName,
        BlackMagicDataLoader $dataLoader
    ) {
        $this->entityManager = $entityManager;
        $this->classMetadata = $classMetadata;
        $this->classMetadataReflection = new ReflectionObject($classMetadata);
        $this->column = $column;
        $this->fieldName = $fieldName;
        $this->dataLoader = $dataLoader;
    }

    public function getDeclaringClass(): ReflectionClass
    {
        Assert::notNull($this->classMetadata->reflClass, sprintf(
            'Tried to get declaring class of property "%s" when class-metadata reflection has not been initialized!',
            $this->classMetadata->name . '#' . $this->fieldName
        ));
        
        return $this->classMetadata->reflClass;
    }

    public function getName(): string
    {
        return $this->fieldName;
    }

    public function getValue(?object $object = null): mixed
    {
        return $this->dataLoader->onColumnValueRequestedFromEntity(
            $this->entityManager,
            $object,
            $this->column->getName()
        );
    }

    public function setValue($valueOrObject, $value = null): void
    {
        if (is_null($value)) {
            $this->dataLoader->onColumnValueSetOnEntity(
                $this->entityManager,
                null,
                $this->column->getName(),
                $valueOrObject
            );

        } else {
            $this->dataLoader->onColumnValueSetOnEntity(
                $this->entityManager,
                $valueOrObject,
                $this->column->getName(),
                $value
            );
        }
    }

    public function getDefaultValue(): mixed
    {
        return null;
    }

    public function getDocComment(): string|false
    {
        return false;
    }

    public function getModifiers(): int
    {
        return 0;
    }

    /** 
     * @psalm-suppress MissingImmutableAnnotation https://github.com/vimeo/psalm/issues/8604
     * @psalm-immutable 
     */
    public function getType(): ?ReflectionType
    {
        return null;
    }

    public function hasDefaultValue(): bool
    {
        return false;
    }

    public function hasType(): bool
    {
        return false;
    }

    public function isDefault(): bool
    {
        return false;
    }

    public function isInitialized($object = null): bool
    {
        return true;
    }

    public function isPrivate(): bool
    {
        return true;
    }

    public function isProtected(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return false;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function setAccessible($accessible): void
    {
    }

    public function __toString(): string
    {
        return "";
    }
}
