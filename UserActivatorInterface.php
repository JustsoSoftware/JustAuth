<?php
/**
 * Definition of UserActivatorInterface.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\RequestHelper;

/**
 * Interface UserActivatorInterface
 */
interface UserActivatorInterface
{
    public function setInfo($code, UserInterface $user, RequestHelper $request);

    public function activateUser(UserInterface $user);
}
