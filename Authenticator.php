<?php
/**
 * Definition of Authenticator.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
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

    public function __construct(SystemEnvironmentInterface $env)
    {
        $this->env = $env;
        $this->session = $env->newInstanceOf('Auth.Session');
    }

    /**
     * Authenticates a user via the provided credentials.
     * If the 'auto-register' auth config option is set, unknown e-mail addresses are used to create new users.
     */
    public function auth()
    {
        $userRepository = $this->getUserRepository();
        $request = $this->env->getRequestHelper();
        try {
            $user = $userRepository->getByEmail($request->getEMailParam('email'));
            $password = $request->getParam('password', '', true);
            if ($password === '') {
                $this->requestActivation($user, $request);
            } elseif ($user->checkPassword($password)) {
                $this->session->loginUser($user);
            } else {
                $user = null;
            }
        } catch (NotFoundException $e) {
            if ($this->getAuthConf('auto-register')) {
                /** @var UserInterface $user */
                $user = $this->env->newInstanceOf('UserInterface');
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
            }
        }
    }

    /**
     * Checks if the user is authenticated.
     *
     * @return bool
     */
    public function isAuth()
    {
        return $this->session->isAuth();
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
        $url = $user->getDestination();
        $this->getLoginNotificator()->activateUser($user);
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
        $code = md5(microtime());
        $user->setToken($code);
        $user->setDestination($request->getParam('page', '', true));
        $userRepository->persist($user);

        $link = Bootstrap::getInstance()->getApiUrl() . '/activate?c=' . $code;
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
            $config = Bootstrap::getInstance()->getConfiguration();
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
            $userRepo = $this->env->newInstanceOf('UserRepositoryInterface');
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
        return $this->env->newInstanceOf('UserRepositoryInterface');
    }

    /**
     * @return LoginNotificatorInterface
     */
    private function getLoginNotificator()
    {
        return $this->env->newInstanceOf('LoginNotificatorInterface');
    }

    /**
     * Returns an information structure containing data about the current user
     *
     * @return array
     */
    public function getAuthInfo()
    {
        $activationPending = $this->isActivationPending();
        $newUser = $this->isNewUser();

        $result = [
            'errors' => [],
            'userid' => null
        ];
        if ($newUser) {
            $result['new_user'] = true;
        }
        if ($activationPending) {
            $result['pending_activation'] = true;
        }
        if (!$activationPending || $newUser) {
            $result['userid'] = $this->getUserId();
        }
        return $result;
    }
}
