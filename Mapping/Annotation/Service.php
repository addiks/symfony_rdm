<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Service
{

    /**
     * The service-id of the service to load for given entitiy-field.
     *
     * @var string
     */
    public $id = "";

    /**
     * Set this to true if this field should not be checked for the correct service on persist.
     * This check is a safety-net and you should know what you are doing when you are disabling it.
     * You have been warned.
     *
     * @var bool
     */
    public $lax = false;

}
