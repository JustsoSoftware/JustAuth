<?php
/**
 * Definition of PasswordAuthenticator.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\RequestHelper;

/**
 * Class PasswordAuthenticator
 */
class PasswordAuthenticator extends Authenticator
{
    protected function findUser(RequestHelper $request, UserRepositoryInterface $userRepository)
    {
        $this->user = $userRepository->getByEmail($request->getEMailParam('email'));
        $password = $request->getParam('password', '', true);
        if ($password === '') {
            $this->needsActivation = true;
        } else if ($this->user->checkPassword($password)) {
            $this->newUser = false;
        } else {
            $this->user = null;
        }
    }
}
