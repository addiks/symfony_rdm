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

namespace Addiks\RDMBundle\Doctrine;

use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class EntityManagerDecorator implements EntityManagerInterface
{

    public function __construct(
        private EntityManagerInterface $inner,
        private DataLoaderInterface $dataLoader
    ) {
        $dataLoader->boot($inner);
    }
    
    public function getCache()
    {
        return $this->inner->getCache();
    }

    public function getConnection()
    {
        return $this->inner->getConnection();
    }

    public function getExpressionBuilder()
    {
        return $this->inner->getExpressionBuilder();
    }

    public function beginTransaction()
    {
        return $this->inner->beginTransaction();
    }

    public function transactional($func)
    {
        return $this->inner->transactional($func);
    }

    public function commit()
    {
        return $this->inner->commit();
    }

    public function rollback()
    {
        return $this->inner->rollback();
    }

    public function createQuery($dql = '')
    {
        return $this->inner->createQuery($dql);
    }

    public function createNamedQuery($name)
    {
        return $this->inner->createNamedQuery($name);
    }

    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        return $this->inner->createNativeQuery($sql, $rsm);
    }

    public function createNamedNativeQuery($name)
    {
        return $this->inner->createNamedNativeQuery($name);
    }

    public function createQueryBuilder()
    {
        return $this->inner->createQueryBuilder();
    }

    public function getReference($entityName, $id)
    {
        return $this->inner->getReference($entityName, $id);
    }

    public function getPartialReference($entityName, $identifier)
    {
        return $this->inner->getPartialReference($entityName, $identifier);
    }

    public function close()
    {
        return $this->inner->close();
    }

    public function copy($entity, $deep = false)
    {
        return $this->inner->copy($entity, $deep);
    }

    public function lock($entity, $lockMode, $lockVersion = null)
    {
        return $this->inner->lock($entity, $lockMode, $lockVersion);
    }

    public function getEventManager()
    {
        return $this->inner->getEventManager();
    }

    public function getConfiguration()
    {
        return $this->inner->getConfiguration();
    }

    public function isOpen()
    {
        return $this->inner->isOpen();
    }

    public function getUnitOfWork()
    {
        return $this->inner->getUnitOfWork();
    }

    public function getHydrator($hydrationMode)
    {
        return $this->inner->getHydrator($hydrationMode);
    }

    public function newHydrator($hydrationMode)
    {
        return $this->inner->newHydrator($hydrationMode);
    }

    public function getProxyFactory()
    {
        return $this->inner->getProxyFactory();
    }

    public function getFilters()
    {
        return $this->inner->getFilters();
    }

    public function isFiltersStateClean()
    {
        return $this->inner->isFiltersStateClean();
    }

    public function hasFilters()
    {
        return $this->inner->hasFilters();
    }

    public function find(string $className, $id)
    {
        return $this->inner->find($className, $id);
    }

    public function persist(object $object)
    {
        return $this->inner->persist($object);
    }

    public function remove(object $object)
    {
        return $this->inner->remove($object);
    }

    public function clear()
    {
        return $this->inner->clear();
    }

    public function detach(object $object)
    {
        return $this->inner->detach($object);
    }

    public function refresh(object $object)
    {
        return $this->inner->refresh($object);
    }

    public function flush()
    {
        return $this->inner->flush();
    }

    public function getRepository($className)
    {
        return $this->inner->getRepository($className);
    }

    public function getClassMetadata($className)
    {
        return $this->inner->getClassMetadata($className);
    }

    public function getMetadataFactory()
    {
        return $this->inner->getMetadataFactory();
    }

    public function initializeObject(object $obj)
    {
        return $this->inner->initializeObject($obj);
    }

    public function contains(object $object)
    {
        return $this->inner->contains($object);
    }
}
