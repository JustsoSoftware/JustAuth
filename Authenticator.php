<?php
/**
 * Definition of Authenticator.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\NotFoundException;
use justso\justapi\RequestHelper;
use justso\justapi\SystemEnvironmentInterface;

/**
 * Class Authenticator
 */
class Authenticator
{
    /**
     * @var SystemEnvironmentInterface
     */
    protected $env;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var array
     */
    private $authConf = null;

    private $errors = [];

    public function __construct(SystemEnvironmentInterface $env)
    {
        $this->env = $env;
        $this->session = $env->getDIC()->get('Auth.Session');
    }

    /**
     * Authenticates a user via the provided credentials.
     * If the 'auto-register' auth config option is set, unknown e-mail addresses are used to create new users.
     */
    public function auth()
    {
        $userRepository = $this->getUserRepository();
        $request = $this->env->getRequestHelper();
        $user = null;
        try {
            $user = $userRepository->getByEmail($request->getEMailParam('email'));
            $password = $request->getParam('password', '', true);
            if ($password === '') {
                $this->requestActivation($user, $request);
            } elseif ($user->checkPassword($password)) {
                $this->session->loginUser($user);
            } else {
                $user = null;
                $this->errors[] = 'auth-failed';
            }
        } catch (NotFoundException $e) {
            if ($this->getAuthConf('auto-register')) {
                /** @var UserInterface $user */
                $user = $this->env->getDIC()->get('UserInterface');
                $user->setFromRequest($request);
                $userRepository->persist($user);

                if ($this->getAuthConf('needs-activation')) {
                    $this->requestActivation($user, $request);
                } else {
                    $user->setActive(true);
                }
                if ($this->getAuthConf('login-new-users')) {
                    $this->session->loginUser($user, true);
                }
            } else {
                $this->errors[] = 'auth-failed';
            }
        }
        return $user;
    }

    /**
     * Checks if the user is authenticated.
     *
     * @return bool
     */
    public function isAuth()
    {
        return $this->session->isAuth() && !($this->getAuthConf('needs-activation') && $this->isActivationPending());
    }

    /**
     * Checks the given access code, and if it is valid, the user is authenticated and activated.
     *
     * @param string $code
     * @return string       The destination string which was registered for the token.
     * @throws NotFoundException if the token is not valid.
     */
    public function activate($code)
    {
        $userRepository = $this->getUserRepository();
        $user = $userRepository->getByAccessCode($code);
        $this->getLoginNotificator()->activateUser($user);
        $url = $user->getDestination();
        $user->setToken(null);
        $user->setDestination(null);
        $user->setActive(true);
        $userRepository->persist($user);
        $this->session->loginUser($user);
        return $url;
    }

    /**
     * Sends an activation link to the specified user.
     *
     * @param UserInterface           $user
     * @param RequestHelper           $request
     */
    private function requestActivation(UserInterface $user, RequestHelper $request)
    {
        $userRepository = $this->getUserRepository();
        $code = bin2hex(openssl_random_pseudo_bytes(20));
        $user->setToken($code);
        $user->setDestination($request->getParam('page', '', true));
        $userRepository->persist($user);
        $this->session->loginUser($user, true);

        $link = $this->env->getBootstrap()->getApiUrl() . '/activate?c=' . $code;
        $this->getLoginNotificator()->sendActivation($user, $code, $link, $request);
    }

    /**
     * Returns the value of the specified authentication configuration value if it is set or the $default.
     *
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    protected function getAuthConf($name, $default = false)
    {
        if ($this->authConf === null) {
            $config = $this->env->getBootstrap()->getConfiguration();
            if (!isset($config['auth'])) {
                $this->authConf = [
                    'auto-register'    => true,
                    'needs-activation' => true,
                ];
            } else {
                $this->authConf = $config['auth'];
            }
        }
        if (isset($this->authConf[$name])) {
            return $this->authConf[$name];
        } else {
            return $default;
        }
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        $user = $this->session->getCurrentUser();
        return $user !== null ? $user->getId() : null;
    }

    /**
     * Returns the user object for the current user from the repository.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        $user = $this->session->getCurrentUser();
        if ($user === null) {
            return null;
        }
        if ($this->session->isCloned()) {
            /** @var UserRepositoryInterface $userRepo */
            $userRepo = $this->env->getDIC()->get('UserRepositoryInterface');
            $user = $userRepo->getById($user->getId());
        }
        return $user;
    }

    /**
     * If a user is authenticated, the return value specifies if the user has just registered.
     * If no user is authenticated, the return value is null.
     *
     * @return bool|null
     */
    public function isNewUser()
    {
        return $this->session->hasJustRegistered();
    }

    /**
     * If a user is authenticated, the return value specifies if there is an activation pending.
     * If no user is authenticated, the return value is null.
     *
     * @return bool|null
     */
    public function isActivationPending()
    {
        $user = $this->session->getCurrentUser();
        return $user !== null ? $user->getToken() != '' : null;
    }

    /**
     * @return UserRepositoryInterface
     */
    private function getUserRepository()
    {
        return $this->env->getDIC()->get('UserRepositoryInterface');
    }

    /**
     * @return LoginNotificatorInterface
     */
    private function getLoginNotificator()
    {
        return $this->env->getDIC()->get('LoginNotificatorInterface');
    }

    /**
     * Returns an information structure containing data about the current user
     *
     * @return array
     */
    public function getAuthInfo()
    {
        $activationPending = $this->isActivationPending();
        $newUser = $this->isNewUser() && $this->getAuthConf('login-new-users');

        $result = [
            'errors' => $this->errors,
            'userid' => null
        ];
        $this->errors = [];
        if ($newUser) {
            $result['new_user'] = true;
        }
        if ($activationPending) {
            $result['pending_activation'] = true;
        }
        if (!$activationPending || $newUser) {
            $result['userid'] = $this->getUserId();
            if ($this->getUserId()) {
                $result['username'] = $this->getUser()->getFullName();
            }
        }
        return $result;
    }

    /**
     * Log out current user
     */
    public function logout()
    {
        $this->session->logoutCurrentUser();
    }
}
