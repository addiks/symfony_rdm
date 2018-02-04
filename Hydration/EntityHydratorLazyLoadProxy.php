<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Hydration;

use Addiks\RDMBundle\Hydration\EntityHydratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class EntityHydratorLazyLoadProxy implements EntityHydratorInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var EntityHydratorInterface
     */
    private $actualHydrator;

    public function __construct(ContainerInterface $container, string $serviceId)
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function hydrateEntity($entity, EntityManagerInterface $entityManager)
    {
        return $this->loadActualHydrator()->hydrateEntity($entity, $entityManager);
    }

    public function assertHydrationOnEntity($entity, EntityManagerInterface $entityManager)
    {
        return $this->loadActualHydrator()->assertHydrationOnEntity($entity, $entityManager);
    }

    private function loadActualHydrator(): EntityHydratorInterface
    {
        if (is_null($this->actualHydrator)) {
            $this->actualHydrator = $this->container->get($this->serviceId);
        }

        return $this->actualHydrator;
    }

}
