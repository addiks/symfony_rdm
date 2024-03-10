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

namespace Addiks\RDMBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Alias;

final class RDMCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->setAlias(
            'addiks_rdm.doctrine.orm.configuration', 
            new Alias(sprintf(
                'doctrine.orm.%s_configuration',
                $this->findDoctrineConnectionName($container) ?? 'default'
            ))
        );
    }
    
    private function findDoctrineConnectionName(ContainerBuilder $container): ?string
    {
        /** @var array<int, array<string, mixed>> $doctrineConfigs */
        $doctrineConfigs = $container->getExtensionConfig('doctrine');
        
        foreach ($doctrineConfigs as $doctrineConfig) {
            foreach ($doctrineConfig['dbal']['connections'] ?? [] as $name => $connection) {
                return $name;
            }
        }
        
        return null;
    }
    
}
