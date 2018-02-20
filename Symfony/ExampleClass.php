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

namespace Addiks\RDMBundle\Symfony;
use Addiks\RDMBundle\Symfony\ExampleTrait;

final class ExampleClass
{

    use ExampleTrait;

    /**
     * @var int
     */
    private $bar;

    public function __construct(string $foo, int $bar)
    {
        $this->initExampleTrait($foo);
        $this->bar = $bar;
    }

    public function getBar(): int
    {
        return $this->bar;
    }

}
