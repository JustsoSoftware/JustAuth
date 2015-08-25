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
abstract class Authenticator
{
    /**
     * @var SystemEnvironmentInterface
     */
    protected $env;

    /**
     * @var array
     */
    private $authConf = null;

    /**
     * Current user
     *
     * @var UserInterface
     */
    protected $user = null;

    /**
     * Indicates that $this->user is only a clone of a 'real' user (normally coming from reading from the session)
     * @var bool
     */
    protected $userIsCloned;

    /**
     * @var bool
     */
    protected $newUser = null;

    /**
     * @var bool
     */
    protected $needsActivation = null;

    public function __construct(SystemEnvironmentInterface $env)
    {
        $this->env = $env;
        $this->needsActivation = $this->getAuthConf('needs-activation');
        $session = $this->env->getSession();
        if ($session->isValueSet('user')) {
            $this->user = $session->getValue('user');
            $this->userIsCloned = true;
        }
    }

    /**
     * Finds the user in the storage and authenticates him with the given credentials.
     *
     * @param RequestHelper           $request
     * @param UserRepositoryInterface $userRepository
     * @throws NotFoundException
     */
    abstract protected function findUser(RequestHelper $request, UserRepositoryInterface $userRepository);

    /**
     * Authenticates a user via the provided credentials.
     * If the 'auto-register' auth config option is set, unknown e-mail addresses are used to create new users.
     */
    public function auth()
    {
        $userRepository = $this->getUserRepository();
        $request = $this->env->getRequestHelper();
        try {
            $this->findUser($request, $userRepository);
            if ($this->needsActivation) {
                $this->requestActivation($this->user, $request);
                if (!$this->getAuthConf('needs-activation')) {
                    $this->user = null;
                }
            }
        } catch (NotFoundException $e) {
            $this->newUser = true;
            if ($this->getAuthConf('auto-register')) {
                $this->registerNewUser($request, $userRepository);
            }
        }

        $this->userIsCloned = false;
        $this->env->getSession()->setValue('user', $this->user);
    }

    /**
     * Checks if the user is authenticated.
     *
     * @return bool
     */
    public function isAuth()
    {
        $session = $this->env->getSession();
        if (!$session->isValueSet('user')) {
            return false;
        }
        $this->user = $session->getValue('user');
        $this->userIsCloned = true;
        return !$this->needsActivation || $this->user->isActive();
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
        $this->user = $userRepository->getByAccessCode($code);
        $url = $this->user->getDestination();
        $this->getUserActivator()->activateUser($this->user);
        $this->user->setToken(null);
        $this->user->setDestination(null);
        $this->user->setActive(true);
        $userRepository->persist($this->user);
        $this->userIsCloned = false;
        $this->env->getSession()->setValue('user', $this->user);
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
        $code = md5(microtime());
        $user->setToken($code);
        $user->setDestination($request->getParam('page', '', true));

        $link = Bootstrap::getInstance()->getApiUrl() . '/activate?c=' . $code;
        $mailer = $this->getLoginNotificator();
        $mailer->sendActivationLink($this->user, $link);

        $activator = $this->env->newInstanceOf('UserActivatorInterface');
        $activator->setInfo($code, $this->user, $request);
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
     * @param RequestHelper $request
     * @param UserRepositoryInterface $userRepository
     */
    private function registerNewUser(RequestHelper $request, UserRepositoryInterface $userRepository)
    {
        $this->user = $this->env->newInstanceOf('UserInterface');
        $this->user->setFromRequest($request);
        $this->newUser = true;
        if ($this->needsActivation) {
            $this->requestActivation($this->user, $request);
        } else {
            $this->user->setActive(true);
        }
        $userRepository->persist($this->user);
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        return $this->user !== null ? $this->user->getId() : null;
    }

    /**
     * Returns the user object for the current user from the repository.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        if ($this->user === null) {
            return null;
        } elseif ($this->userIsCloned) {
            $userRepo = $this->env->newInstanceOf('UserRepositoryInterface');
            $this->user = $userRepo->getById($this->user->getId());
            $this->userIsCloned = false;
        }
        return $this->user;
    }

    /**
     * If a user is authenticated, the return value specifies if the user has just registered (in the same request).
     * If no user is authenticated, the return value is null.
     *
     * @return bool|null
     */
    public function isNewUser()
    {
        return $this->user !== null ? $this->newUser : null;
    }

    /**
     * If a user is authenticated, the return value specifies if there is an activation pending.
     * If no user is authenticated, the return value is null.
     *
     * @return bool|null
     */
    public function isActivationPending()
    {
        return $this->user !== null ? $this->user->isActive() : null;
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
     * @return UserActivatorInterface
     */
    private function getUserActivator()
    {
        return $this->env->newInstanceOf('UserActivatorInterface');
    }
}
