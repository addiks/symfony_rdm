<?php

namespace Addiks\RDMBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;

class AddiksRDMBundle extends Bundle
{
    public function boot()
    {
        Assert::isInstanceOf($this->container, ContainerInterface::class);

        /** @var DataLoaderInterface $dataLoader */
        $dataLoader = $this->container->get('addiks_rdm.data_loader');

        Assert::isInstanceOf($dataLoader, DataLoaderInterface::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        Assert::isInstanceOf($entityManager, EntityManagerInterface::class);

        $dataLoader->boot($entityManager);
    }
}
