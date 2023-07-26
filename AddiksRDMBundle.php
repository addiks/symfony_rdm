<?php

namespace Addiks\RDMBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Composer\Autoload\ClassLoader;

class AddiksRDMBundle extends Bundle
{
    private static ClassLoader|null $classLoader = null;

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

    public static function classLoader(): ClassLoader|null
    {
        return self::$classLoader;
    }

    public static function registerClassLoader(ClassLoader $classLoader): void
    {
        self::$classLoader = $classLoader;
    }
}
