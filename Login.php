<?php
/**
 * Definition of class Login
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\InvalidParameterException;
use justso\justapi\RestService;
use justso\justapi\NotFoundException;

/**
 * Login service
 *
 * @package justso\innolab
 */
class Login extends RestService
{
    /**
     * Returns information about login status of the current session
     */
    public function getAction()
    {
        $authenticator = $this->getAuthenticator();
        $this->environment->sendJSONResult($authenticator->getAuthInfo());
    }

    /**
     * Logs in or registers the user with the specified information.
     * The config entry auth.auto-register controls if unknown e-mail addresses can be used to automatically register
     * new users.
     * If config entry auth.needs-activation is set, an activation link is sent to the specified e-mail-address.
     *
     * @throws InvalidParameterException
     * @throws NotFoundException
     */
    public function postAction()
    {
        $authenticator = $this->getAuthenticator();
        $authenticator->auth();
        $this->environment->sendJSONResult($authenticator->getAuthInfo());
    }

    public function deleteAction()
    {
        $authenticator = $this->getAuthenticator();
        if ($authenticator->isAuth()) {
            $authenticator->logout();
        }
        $this->environment->sendJSONResult('logged-out');
    }

    /**
     * @return Authenticator
     */
    private function getAuthenticator()
    {
        return $this->environment->newInstanceOf('Authenticator');
    }
}
