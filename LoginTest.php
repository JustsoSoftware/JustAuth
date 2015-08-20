<?php
/**
 * Definition of LoginTest
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
use justso\justapi\NotFoundException;
use justso\justapi\testutil\ServiceTestBase;
use justso\justapi\testutil\TestEnvironment;

/**
 * Class LoginTest
 *
 * @package justso\innolab\test
 */
class LoginTest extends ServiceTestBase
{
    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function provideUserIds()
    {
        return [[ null ], [ 123 ]];
    }

    /**
     * @dataProvider provideUserIds
     */
    public function testGet($id)
    {
        $env = $this->setupEnvironment(true, true);
        if ($id > 0) {
            $env->getSession()->setValue('userid', $id);
        }

        $service = new Login($env);
        $service->getAction();

        $this->assertJSONHeader($env);
        $result = json_decode($env->getResponseContent(), true);
        $this->assertSame(['errors' => [], 'userid' => $id], $result);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function provideEMailOnlyParameters()
    {
        return [[ true, true ], [ true, false ], [ false, true ], [ false, false ]];
    }

    /**
     * @param bool $needsActivation
     * @dataProvider provideEMailOnlyParameters
     */
    public function testLoginNewUserWithEMailOnly($needsActivation, $autoRegister)
    {
        $env = $this->setupEnvironment($needsActivation, $autoRegister);

        $userMock = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        if ($autoRegister) {
            $userMock->expects($this->once())->method('setFromRequest');
            $this->checkActivationLink($needsActivation, $env, $userMock);
        } else {
            $userMock->expects($this->never())->method('setFromRequest');
            $this->setExpectedException('justso\\justapi\\NotFoundException');
        }
        $repoMock = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repoMock->expects($this->once())->method('getByEMail')->willThrowException(new NotFoundException());

        $service = new Login($env);
        $service->postAction();

        $this->assertJSONHeader($env);
        $result = json_decode($env->getResponseContent(), true);
        $this->assertSame(['errors' => [], 'userid' => null, 'new_user' => true], $result);
    }

    /**
     * @param bool $needsActivation
     * @dataProvider provideEMailOnlyParameters
     */
    public function testLoginExistingUserWithEMailOnly($needsActivation, $autoRegister)
    {
        $env = $this->setupEnvironment($needsActivation, $autoRegister);

        $userMock = $this->mockInterface('justso\\justauth', 'UserInterface', $env);
        $repoMock = $this->mockInterface('justso\\justauth', 'UserRepositoryInterface', $env);
        $repoMock->expects($this->once())
            ->method('getByEMail')->with('test@justso.de')->will($this->returnValue($userMock));
        $this->checkActivationLink($needsActivation, $env, $userMock);

        $service = new Login($env);
        $service->postAction();

        $this->assertJSONHeader($env);
        $result = json_decode($env->getResponseContent(), true);
        $this->assertSame(['errors' => [], 'userid' => null], $result);
    }

    /**
     * @expectedException \justso\justapi\InvalidParameterException
     */
    public function testWithInvalidAuthMethod()
    {
        $env = $this->setupEnvironment(true, true);
        $env->getRequestHelper()->set(['email' => 'test@justso.de', 'login-type' => 'invalid']);

        $service = new Login($env);
        $service->postAction();
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
                'allowedMethods' => [Login::AUTH_EMAIL_ONLY],
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
     * @param $userMock
     */
    private function checkActivationLink($needsActivation, $env, $userMock)
    {
        $notiMock = $this->mockInterface('justso\\justauth', 'LoginNotificatorInterface', $env);
        $actiMock = $this->mockInterface('justso\\justauth', 'UserActivatorInterface', $env);
        if ($needsActivation) {
            $notiMock->expects($this->once())->method('sendActivationLink')->with($userMock);
            $actiMock->expects($this->once())->method('setInfo');
        } else {
            $notiMock->expects($this->never())->method('sendActivationLink');
            $actiMock->expects($this->never())->method('setInfo');
        }
    }
}
