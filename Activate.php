<?php
/**
 * Definition of Activate Service
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
use justso\justapi\DenyException;
use justso\justapi\RestService;

class Activate extends RestService
{
    public function getAction()
    {
        $request = $this->environment->getRequestHelper();
        $code = $request->getHexParam('c');
        $session = $this->environment->getSession();
        $session->activate();
        if ($session->isValueSet('activationCode') && $code === $session->getValue('activationCode')) {
            $userRepo = $this->getUserRepository();
            $user = $userRepo->loginWithCode($code);

            if (!$user->isActive()) {
                $activator = $this->getUserActivator();
                $activator->activateUser($user);
            }

            $page = $session->isValueSet('currentPage') ? $session->getValue('currentPage') : '';
            $url = Bootstrap::getInstance()->getWebAppUrl() . '/' . $page;
            $this->environment->sendHeader('Location: ' . $url);
        } else {
            throw new DenyException(
                "Invalid Activation Code\n\nMake sure you use the same browser for activating as " .
                "the one you used for logging in. Try copy and paste the link from the e-mail to your browser."
            );
        }
    }

    /**
     * @return UserRepositoryInterface
     */
    private function getUserRepository()
    {
        return $this->environment->newInstanceOf('UserRepositoryInterface');
    }

    /**
     * @return UserActivatorInterface
     */
    private function getUserActivator()
    {
        return $this->environment->newInstanceOf('UserActivatorInterface');
    }
}
