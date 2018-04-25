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

use Doctrine\ORM\EntityManagerInterface;

/**
 * Represents the context of one hydration-process.
 */
interface HydrationContextInterface
{

    /**
     * @return object
     */
    public function getEntity();

    /**
     * Get's the _effective_ absolute class of the entity.
     * (Resolves doctrine-proxy-classes.)
     */
    public function getEntityClass(): string;

    /**
     * Allows registering of named values during hydration.
     *
     * This allows to re-use values (mostly objects) on multiple points in the mapping.
     * (The same object may occur in multiple different locations in an object graph.)
     *
     * @param mixed $value
     */
    public function registerValue(string $id, $value): void;

    public function hasRegisteredValue(string $id): bool;

    /**
     * Returns previously registered value for given $id.
     * Throws exception if there is no value registered for $id.
     * Check with hasRegisteredValue if value is registered beforehand.
     *
     * @return mixed
     *
     * @throws InvalidMappingException
     */
    public function getRegisteredValue(string $id);

    /**
     * Get's the current stack for cascading objects (or values) during hydration.
     * First entry is always the entity (root of the aggregate) itself.
     * Last entry is always the innermost object that is currently being hydrated.
     *
     * Example:
     *  [$car, $wheelSuspension, $tire, $felly]
     *  The root-entity (of the aggregate) is the car, but we currently hydrate the felly of the tire.
     */
    public function getObjectHydrationStack(): array;

    public function pushOnObjectHydrationStack($value): void;

    /**
     * @return mixed
     */
    public function popFromObjectHydrationStack();

    public function getEntityManager(): EntityManagerInterface;

}
