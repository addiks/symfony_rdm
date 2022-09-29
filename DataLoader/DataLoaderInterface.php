<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\DataLoader;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

interface DataLoaderInterface
{
    /** To be called at the start of the process */
    public function boot(EntityManagerInterface $entityManager): void;

    /**
     * @var array<string>
     */
    public function loadDBALDataForEntity($entity, EntityManagerInterface $entityManager): array;

    /**
     * Checks given entity for changed RDM-relevant data.
     * If changes were found, the changes will be stored in the database.
     */
    public function storeDBALDataForEntity($entity, EntityManagerInterface $entityManager);

    /**
     * Removes the RDM-relevant data from the database.
     *
     * In most cases this is not needed because the RDM-relevant data is stored in the table representing the entity and
     * thus will be deleted when doctrine itself deletes the row. It _could_ be the case however that data was stored
     * elsewhere and needs to be removed separate.
     */
    public function removeDBALDataForEntity($entity, EntityManagerInterface $entityManager);

    /**
     * Prepares the data-loader at the point where the metadata of an entity get loaded by doctrine.
     * This gives the data-loader a chance to "hook" into doctrine before any queries get executed.
     */
    public function prepareOnMetadataLoad(EntityManagerInterface $entityManager, ClassMetadata $classMetadata);

}
