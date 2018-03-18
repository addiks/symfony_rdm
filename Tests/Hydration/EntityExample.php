<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Hydration;

use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Tests\ValueObjectExample;

class EntityExample
{

    /**
     * @var string
     */
    public $id;

    /**
     * @var ServiceExample
     */
    public $foo;

    /**
     * @var ServiceExample
     */
    public $bar;

    /**
     * @var string
     */
    public $baz;

    /**
     * @var string
     */
    private $faz;

    /**
     * @var null|ValueObjectExample
     */
    public $boo;

    /**
     * @var null|mixed
     */
    public $abc;

    /**
     * @var null|mixed
     */
    public $def;

    public function __construct(
        ServiceExample $foo = null,
        ServiceExample $bar = null,
        ServiceExample $baz = null,
        ServiceExample $faz = null,
        ValueObjectExample $boo = null
    ) {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
        $this->faz = $faz;
        $this->boo = $boo;
    }

    public static $staticMetadata;

    public static function loadRDMMetadata()
    {
        return self::$staticMetadata;
    }

    public function getFaz(): ?ServiceExample
    {
        return $this->faz;
    }

    public function getBoo(): ?ValueObjectExample
    {
        return $this->boo;
    }

}
