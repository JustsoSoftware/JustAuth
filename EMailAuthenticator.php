<?php
/**
 * Definition of EMailAuthenticator.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\RequestHelper;

/**
 * Class EMailAuthenticator
 */
class EMailAuthenticator extends Authenticator
{
    protected function findUser(RequestHelper $request, UserRepositoryInterface $userRepository)
    {
        $this->user = $userRepository->getByEmail($request->getEMailParam('email'));
        $this->newUser = false;
    }
}
