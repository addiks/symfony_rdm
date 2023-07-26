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

namespace Addiks\SymfonyRDM;

use Composer\Autoload\ClassLoader;
use Symfony\Component\HttpKernel\KernelInterface;
use Psr\Container\ContainerInterface;
use Addiks\RDMBundle\AddiksRDMBundle;

function symfony_rdm_composer_hook(ClassLoader $classLoader, KernelInterface $kernel): void
{
    AddiksRDMBundle::registerClassLoader($classLoader);
    
    $cacheDir = $kernel->getCacheDir();
    
    $classmapFilePath = $cacheDir . '/symfony_rdm_entities/classmap';
    
    if (file_exists($classmapFilePath)) {
        $classMap = array();
        
        foreach (explode("\n", file_get_contents($classmapFilePath)) as $classmapLine) {
            if (empty($classmapLine)) {
                continue;
            }
            
            [$className, $filePath] = explode(":", $classmapLine);
            
            $classMap[$className] = $filePath;
        }
        
        $classLoader->addClassMap($classMap);
    }
}
