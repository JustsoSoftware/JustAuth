<?php
/**
 * Definition of class Login
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
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
    const AUTH_EMAIL_ONLY = 'email-auth';
    const AUTH_EMAIL_PWD = 'email-plus-pwd';

    public function postAction()
    {
        $request = $this->environment->getRequestHelper();
        $email = $request->getEMailParam('email');

        $method = $request->getIdentifierParam('login-type', 'email-auth', true);
        $authConf = $this->getAuthConf();
        $autoRegister = !empty($authConf['auto-register']);
        $allowedAuths = $authConf['allowedMethods'];
        if (!in_array($method, $allowedAuths)) {
            throw new InvalidParameterException('Unknown auth method');
        }

        $userRepository = $this->getUserRepository();
        try {
            $user = $userRepository->getByEmail($email);
        } catch (NotFoundException $e) {
            if ($autoRegister) {
                $user = $this->getUser();
                $user->setFromRequest($request);
                $userRepository->register($user);
            } else {
                throw $e;
            }
        }

        if (!empty($authConf['needs-activation'])) {
            $code = $this->sendActivationLink($userRepository, $user);
            $activator = $this->environment->newInstanceOf('UserActivatorInterface');
            $activator->setInfo($code, $user, $request);
            $id = null;
        } else {
            $id = $user->getId();
        }

        $this->environment->sendJSONResult(['errors' => [], 'userid' => $id]);
    }

    /**
     * Sends an activation link to the specified user.
     *
     * @param UserRepositoryInterface $repo
     * @param UserInterface           $user
     * @return string
     */
    private function sendActivationLink(UserRepositoryInterface $repo, UserInterface $user)
    {
        $code = md5(microtime());
        $repo->setAccessCode($user->getEMail(), $code);
        $link = Bootstrap::getInstance()->getApiUrl() . '/activate?c=' . $code;
        $mailer = $this->getLoginNotificator();
        $mailer->sendActivationLink($user, $link);
        return $code;
    }

    /**
     * @return LoginNotificatorInterface
     */
    private function getLoginNotificator()
    {
        return $this->environment->newInstanceOf('LoginNotificatorInterface');
    }

    /**
     * @return UserRepositoryInterface
     */
    private function getUserRepository()
    {
        return $this->environment->newInstanceOf('UserRepositoryInterface');
    }

    /**
     * @return UserInterface
     */
    private function getUser()
    {
        return $this->environment->newInstanceOf('UserInterface');
    }

    private function getAuthConf()
    {
        $config = Bootstrap::getInstance()->getConfiguration();
        if (!isset($config['auth'])) {
            return [
                'allowedMethods'   => [self::AUTH_EMAIL_ONLY],
                'auto-register'    => true,
                'needs-activation' => true,
            ];
        } else {
            return $config['auth'];
        }
    }
}
