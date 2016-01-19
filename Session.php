<?php
/**
 * Definition of Session.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\SystemEnvironmentInterface;

/**
 * Class Session
 */
class Session
{
    /**
     * @var SystemEnvironmentInterface
     */
    private $env;

    /**
     * Current user
     *
     * @var UserInterface
     */
    private $user = null;

    /**
     * Indicates that the current user has just registered and has not yet activated the account
     * @var bool
     */
    private $justRegistered = null;

    /**
     * Indicates that the user object comes from the session and is therefore a clone of the 'real' user.
     *
     * @var bool
     */
    private $isCloned;

    public function __construct(SystemEnvironmentInterface $env)
    {
        $this->env = $env;
        /** @var \justso\justapi\Session $session */
        $session = $this->env->getSession();
        if ($session->isValueSet('user')) {
            $this->user = $session->getValue('user');
            $this->justRegistered = $session->getValue('justRegistered');
            $this->isCloned = true;
        }
    }

    public function loginUser(UserInterface $user, $justRegistered = false)
    {
        $this->user = $user;
        $this->justRegistered = $justRegistered;

        $session = $this->env->getSession();
        $session->setValue('user', $user);
        $session->setValue('justRegistered', $justRegistered);
        $this->isCloned = false;
    }

    public function logoutCurrentUser()
    {
        $session = $this->env->getSession();
        $session->unsetValue('user');
        $session->unsetValue('justRegistered');
        $this->justRegistered = null;
        $this->user = null;
    }

    /**
     * @return UserInterface
     */
    public function getCurrentUser()
    {
        return $this->user;
    }

    /**
     * Checks if a user is logged in
     * @return bool
     */
    public function isAuth()
    {
        return $this->user !== null;
    }

    public function hasJustRegistered()
    {
        return $this->justRegistered;
    }

    public function isCloned()
    {
        return $this->isCloned;
    }
}
