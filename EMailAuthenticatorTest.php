<?php
/**
 * Definition of TestEMailAuthenticator.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
use justso\justapi\NotFoundException;
use justso\justapi\testutil\TestEnvironment;
use justso\justapi\testutil\ServiceTestBase;

/**
 * Class TestEMailAuthenticator
 */
class TestEMailAuthenticator extends ServiceTestBase
{
    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function provideNeedsActivation()
    {
        return [[ true ], [ false ]];
    }

    /**
     * @param bool $needsActivation
     * @dataProvider provideNeedsActivation
     */
    public function testRegularUserLogin($needsActivation)
    {
        $env = $this->setupEnvironment($needsActivation, false);
        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $user->expects($this->any())->method('getId')->willReturn(123);
        $user->expects($this->once())->method('isActive')->willReturn($needsActivation);
        $user->expects($this->never())->method('setFromRequest');
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEmail')->with('test@justso.de')->willReturn($user);
        $this->checkActivationLink($needsActivation, $env, $user);

        $authenticator = new EMailAuthenticator($env);
        $authenticator->auth();

        $this->assertSame(123, $authenticator->getUserId());
        $this->assertFalse($authenticator->isNewUser());
        $this->assertSame($needsActivation, $authenticator->isActivationPending());
    }

    /**
     * @param bool $needsActivation
     * @dataProvider provideNeedsActivation
     */
    public function testAutoRegisterNewUsers($needsActivation)
    {
        $env = $this->setupEnvironment($needsActivation, true);
        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $user->expects($this->any())->method('getId')->willReturn(123);
        $user->expects($this->once())->method('isActive')->willReturn($needsActivation);
        $user->expects($this->once())->method('setFromRequest');
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEMail')->willThrowException(new NotFoundException());
        $this->checkActivationLink($needsActivation, $env, $user);

        $authenticator = new EMailAuthenticator($env);
        $authenticator->auth();

        $this->assertSame(123, $authenticator->getUserId());
        $this->assertTrue($authenticator->isNewUser());
        $this->assertSame($needsActivation, $authenticator->isActivationPending());
    }

    /**
     * @param bool $needsActivation
     * @dataProvider provideNeedsActivation
     */
    public function testNewUserWithoutAutoRegistration($needsActivation)
    {
        $env = $this->setupEnvironment($needsActivation, false);
        $user = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $user->expects($this->any())->method('getId')->willReturn(123);
        $user->expects($this->never())->method('setFromRequest');
        $repo = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repo->expects($this->once())->method('getByEMail')->willThrowException(new NotFoundException());
        $this->checkActivationLink(false, $env, $user);

        $authenticator = new EMailAuthenticator($env);
        $authenticator->auth();

        $this->assertNull($authenticator->getUserId());
        $this->assertNull($authenticator->isNewUser());
        $this->assertNull($authenticator->isActivationPending());
    }

    /**
     * @return TestEnvironment
     */
    private function setupEnvironment($needsActivation, $autoRegister)
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
                'needs-activation' => $needsActivation
            ]
        ];
        Bootstrap::getInstance()->setTestConfiguration('/my/approot', $config);
        return $env;
    }

    /**
     * @param $needsActivation
     * @param $env
     * @param $user
     */
    private function checkActivationLink($needsActivation, $env, $user)
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
     * Mocks an entry in the DependencyContainer naming an interface.
     *
     * @param string          $namespace
     * @param string          $name
     * @param TestEnvironment $env
     * @return \PHPUnit_FrameWork_MockObject_MockObject
     */
    public function mockInterface($namespace, $name, TestEnvironment $env)
    {
        $mock = $this->getMockForAbstractClass(rtrim($namespace, '\\') . '\\' . $name);
        $env->setDICEntry($name, function () use ($mock) {
            return $mock;
        });
        return $mock;
    }
}
