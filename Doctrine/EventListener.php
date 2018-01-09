<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventArgs;
use Addiks\RDMBundle\Hydration\EntityServiceHydratorInterface;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Hooks into the event's of doctrine2-ORM and forwards the entities to the hydrator.
 */
final class EventListener
{

    /**
     * @var EntityServiceHydratorInterface
     */
    private $entityHydrator;

    public function __construct(
        EntityServiceHydratorInterface $entityHydrator
    ) {
        $this->entityHydrator = $entityHydrator;
    }

    public function postLoad(LifecycleEventArgs $arguments)
    {
        /** @var object $entity */
        $entity = $arguments->getEntity();

        $this->entityHydrator->hydrateEntity($entity);
    }

    public function prePersist(LifecycleEventArgs $arguments)
    {
        /** @var object $entity */
        $entity = $arguments->getEntity();

        $this->entityHydrator->assertHydrationOnEntity($entity);
    }

}
