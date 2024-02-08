<?php

namespace Addiks\RDMBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Addiks\RDMBundle\DataLoader\DataLoaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Composer\Autoload\ClassLoader;
use Addiks\RDMBundle\DependencyInjection\RDMCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddiksRDMBundle extends Bundle
{
    private static ClassLoader|null $classLoader = null;

    public function boot()
    {
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RDMCompilerPass());
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
