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

namespace Addiks\RDMBundle\Hydration;

use Addiks\RDMBundle\Hydration\HydrationContextInterface;
use Webmozart\Assert\Assert;
use Doctrine\Common\Util\ClassUtils;
use Addiks\RDMBundle\Exception\InvalidMappingException;
use Doctrine\ORM\EntityManagerInterface;

final class HydrationContext implements HydrationContextInterface
{

    /**
     * @var object
     */
    private $entity;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array<string, mixed>
     */
    private $registeredValues = array();

    /**
     * @var array<int, mixed>
     */
    private $hydrationStack = array();

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param object $entity
     */
    public function __construct($entity, EntityManagerInterface $entityManager)
    {
        Assert::true(is_object($entity));

        $this->entity = $entity;
        $this->entityClass = get_class($entity);
        $this->hydrationStack[] = $entity;
        $this->entityManager = $entityManager;

        if (class_exists(ClassUtils::class)) {
            $this->entityClass = ClassUtils::getRealClass($this->entityClass);
        }
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function registerValue(string $id, $value): void
    {
        $this->registeredValues[$id] = $value;
    }

    public function hasRegisteredValue(string $id): bool
    {
        return array_key_exists($id, $this->registeredValues);
    }

    public function getRegisteredValue(string $id)
    {
        if (!array_key_exists($id, $this->registeredValues)) {
            throw new InvalidMappingException(sprintf(
                "Tried to load unknown value '%s' from register!",
                $id
            ));
        }

        return $this->registeredValues[$id];
    }

    public function getObjectHydrationStack(): array
    {
        return $this->hydrationStack;
    }

    public function pushOnObjectHydrationStack($value): void
    {
        $this->hydrationStack[] = $value;
    }

    public function popFromObjectHydrationStack()
    {
        Assert::greaterThan(count($this->hydrationStack), 1);

        return array_pop($this->hydrationStack);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

}
