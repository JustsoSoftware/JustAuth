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
use justso\justapi\NotFoundException;
use justso\justapi\RestService;

/**
 * This service is called when the user clicks on an activation link.
 */
class Activate extends RestService
{
    public function getAction()
    {
        try {
            $code = $this->environment->getRequestHelper()->getHexParam('c');
            $authenticator = $this->getAuthenticator();
            $url = $authenticator->activate($code);
            $this->environment->sendHeader('Location: ' . Bootstrap::getInstance()->getWebAppUrl() . '/' . $url);
        } catch (NotFoundException $e) {
            throw new DenyException(
                "Invalid Activation Code\n\nMake sure you use the same browser for activating as " .
                "the one you used for logging in. Try copy and paste the link from the e-mail to your browser."
            );
        }
    }

    /**
     * @return Authenticator
     */
    private function getAuthenticator()
    {
        return $this->environment->newInstanceOf('Authenticator');
    }
}
