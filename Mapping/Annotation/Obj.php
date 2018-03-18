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

namespace Addiks\RDMBundle\Mapping\Annotation;

use Doctrine\ORM\Mapping\Annotation;
use Addiks\RDMBundle\Mapping\Annotation\Call;

/**
 * The object annotation.
 *
 * Why only "Obj" without the "ect"? Because "Object" is a reserved word. :-/
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Obj
{

    /**
     * @var string|null
     */
    public $class;

    /**
     * @var array<object>
     */
    public $fields = array();

    /**
     * @var null|string|Call
     */
    public $factory;

    /**
     * @var null|string|Call
     */
    public $serialize;

}
