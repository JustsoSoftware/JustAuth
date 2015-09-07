<?php
/**
 * Definition of AuthenticatorTest.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
use justso\justapi\NotFoundException;
use justso\justapi\testutil\ServiceTestBase;
use justso\justapi\testutil\TestEnvironment;

class AuthenticatorTest extends ServiceTestBase
{
    /**
     * Provides data for testRegister()
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function provideRegisterParams()
    {
        return[
            [false, false, false],      // don't auto-register new users
            //[false, false, true],     // if new users aren't registered automatically, there is no need to log them in
            //[false, true, false],     // or send an activation link. Only pre-defined users are possible.
            //[false, true, true],      //
            [true, false, false],       // New users aren't logged in automatically, they have to login manually
            [true, false, true],        // New users must activate their account first
            [true, true, false],        // New users are logged in automatically, activation not necessary
            [true, true, true],         // New users are logged in automatically, later logins require activation
        ];
    }

    /**
     * Check registering of new users.
     *
     * @param bool $autoRegister
     * @param bool $autoLogin
     * @dataProvider provideRegisterParams
     */
    public function testRegister($autoRegister, $autoLogin = false, $requireActivation = false)
    {
        $env = $this->setupEnvironment($autoRegister, $autoLogin, $requireActivation);
        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        if ($autoRegister) {
            $user->expects($this->any())->method('getId')->willReturn(123);
            if ($requireActivation) {
                $user->expects($this->once())->method('setToken');
            }
            $user->expects($this->once())->method('setFromRequest');
        } else {
            $user->expects($this->never())->method('setFromRequest');
        }
        $this->checkActivationLink($requireActivation, $env, $user);
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEMail')->willThrowException(new NotFoundException());

        $auth = new Authenticator($env);
        $auth->auth();

        $this->assertSame($autoLogin, $auth->isAuth());
    }

    /**
     * Check 'normal' login with email and password
     */
    public function testLoginWithPassword()
    {
        $env = $this->setupEnvironment(false, false, false);
        $env->getRequestHelper()->fillWithData(['email' => 'test@justso.de', 'password' => 'test123']);
        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $user->expects($this->any())->method('getId')->willReturn(123);
        $user->expects($this->once())->method('checkPassword')->with('test123')->willReturn(true);
        $user->expects($this->never())->method('setFromRequest');
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEmail')->with('test@justso.de')->willReturn($user);
        $this->checkActivationLink(false, $env, $user);

        $authenticator = new Authenticator($env);
        $authenticator->auth();

        $this->assertSame(123, $authenticator->getUserId());
        $this->assertFalse($authenticator->isNewUser());
    }

    public function testLoginWithWrongPassword()
    {
        $env = $this->setupEnvironment(false, false, false);
        $env->getRequestHelper()->fillWithData(['email' => 'test@justso.de', 'password' => 'wrong-password']);
        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $user->expects($this->once())->method('checkPassword')->with('wrong-password')->willReturn(false);
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEmail')->with('test@justso.de')->willReturn($user);
        $this->checkActivationLink(false, $env, $user);

        $authenticator = new Authenticator($env);
        $authenticator->auth();

        $this->assertNull($authenticator->getUserId());
    }

    public function testLoginWithActivationLink()
    {
        $env = $this->setupEnvironment(false, false, true);
        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $user->expects($this->any())->method('getId')->willReturn(123);
        $user->expects($this->never())->method('setFromRequest');
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEmail')->with('test@justso.de')->willReturn($user);
        $this->checkActivationLink(true, $env, $user);

        $authenticator = new Authenticator($env);
        $authenticator->auth();

        $this->assertNull($authenticator->getUserId());
    }

    /**
     * Mock LoginNotificatorInterface and UserActivatorInterface
     *
     * @param bool            $needsActivation
     * @param TestEnvironment $env
     * @param UserInterface   $user
     */
    private function checkActivationLink($needsActivation, TestEnvironment $env, UserInterface $user)
    {
        $notiMock = $this->mockInterface('justso\\justauth', 'LoginNotificatorInterface', $env);
        $actiMock = $this->mockInterface('justso\\justauth', 'UserActivatorInterface', $env);
        if ($needsActivation) {
            $notiMock->expects($this->once())->method('sendActivationLink')->with($user);
            $actiMock->expects($this->once())->method('setInfo');
        } else {
            $notiMock->expects($this->never())->method('sendActivationLink');
            $actiMock->expects($this->never())->method('setInfo');
        }
    }

    /**
     * Check trying to login with an unknown combination of userid and password
     */
    public function testLoginWithUnknownUser()
    {
        $env = $this->setupEnvironment(false, false, false);
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEmail')
            ->with('test@justso.de')->willThrowException(new NotFoundException());

        $authenticator = new Authenticator($env);
        $authenticator->auth();

        $this->assertNull($authenticator->getUserId());
    }

    public function testActivation()
    {
        $code = 'this-is-the-code';
        $env = $this->setupEnvironment(false, false, true);

        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $user->expects($this->once())->method('getDestination')->willReturn('http://example.com');
        $user->expects($this->once())->method('setToken')->with(null);
        $user->expects($this->once())->method('setDestination')->with(null);
        $user->expects($this->once())->method('setActive')->with(true);

        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByAccessCode')->with($code)->willReturn($user);
        $repo->expects($this->once())->method('persist')->with($user);

        $activator = $this->mockInterface('justso\\justauth', 'UserActivatorInterface', $env);
        $activator->expects($this->once())->method('activateUser')->with($user);

        $session = $this->getMock('justso\\justauth\\Session', [], [], '', false);
        $session->expects($this->once())->method('loginUser')->with($user);
        $env->setDICEntry('Auth.Session', $session);

        $authenticator = new Authenticator($env);
        $authenticator->activate($code);
    }

    /**
     * Setup test environment
     *
     * @param $autoRegister
     * @param $autoLogin
     * @param $requireActivation
     * @return \justso\justapi\testutil\TestEnvironment
     */
    private function setupEnvironment($autoRegister, $autoLogin, $requireActivation)
    {
        $env = $this->createTestEnvironment(['email' => 'test@justso.de']);
        $config = [
            'environments' => [
                'test' => [
                    'approot' => '/my/approot',
                    'apiurl' => 'http://test.com/api',
                ]
            ],
            'auth' => [
                'auto-register' => $autoRegister,
                'needs-activation' => $requireActivation,
                'login-new-users' => $autoLogin
            ]
        ];
        Bootstrap::getInstance()->setTestConfiguration('/my/approot', $config);
        $env->setDICEntry('Auth.Session', 'justso\\justauth\\Session');
        return $env;
    }
}
