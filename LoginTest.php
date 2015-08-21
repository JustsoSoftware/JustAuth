<?php
/**
 * Definition of LoginTest
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\testutil\ServiceTestBase;
use justso\justapi\testutil\TestEnvironment;

/**
 * Class LoginTest
 *
 * @package justso\innolab\test
 */
class LoginServiceTest extends ServiceTestBase
{
    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function provideUseCases()
    {
        return [                    // In case of GET                       In case of POST
            [ null, null, null ],   // not logged in                        Wrong credentials
            [ 123, null, null ],    // regular authenticated user           Regular login
            [ 123, true, true ],    // new user, not yet activated          New registration, not yet activated
            [ 123, null, true ],    // new user, no activation required     New registration, no activation required
        ];
    }

    /**
     * @param int $id
     * @param bool $activationPending
     * @param bool $newUser
     * @dataProvider provideUseCases
     */
    public function testGet($id, $activationPending, $newUser)
    {
        $env = $this->setupAuthenticator($id, $activationPending, $newUser);
        $service = new Login($env);
        $service->getAction();
        $this->checkResult($env, $id, $activationPending, $newUser);
    }

    /**
     * @param int $id
     * @param bool $activationPending
     * @param bool $newUser
     * @dataProvider provideUseCases
     */
    public function testLogin($id, $activationPending, $newUser)
    {
        $env = $this->setupAuthenticator($id, $activationPending, $newUser);
        $service = new Login($env);
        $service->postAction();
        $this->checkResult($env, $id, $activationPending, $newUser);
    }

    /**
     * @param int $id
     * @param bool $activationPending
     * @param bool $newUser
     * @return TestEnvironment
     */
    private function setupAuthenticator($id, $activationPending, $newUser)
    {
        $env = $this->createTestEnvironment();
        $authenticator = $this->getMock('justso\\justauth\\PasswordAuthenticator', [], [], '', false);
        $authenticator->expects($this->once())->method('getUserId')->willReturn($id);
        $authenticator->expects($this->once())->method('isActivationPending')->willReturn($activationPending);
        $authenticator->expects($this->once())->method('isNewUser')->willReturn($newUser);
        $env->setDICEntry('Authenticator', function () use ($authenticator) {
            return $authenticator;
        });
        return $env;
    }

    /**
     * @param $result
     * @param $key
     * @return null
     */
    private function arrayValue($result, $key)
    {
        return isset($result[$key]) ? $result[$key] : null;
    }

    /**
     * @param TestEnvironment $env
     * @param int $id
     * @param bool $activationPending
     * @param bool $newUser
     */
    private function checkResult(TestEnvironment $env, $id, $activationPending, $newUser)
    {
        $this->assertJSONHeader($env);
        $result = json_decode($env->getResponseContent(), true);
        $this->assertSame([], $result['errors']);
        $this->assertSame($id, $result['userid']);
        $this->assertSame($activationPending, $this->arrayValue($result, 'pending_actication'));
        $this->assertSame($newUser, $this->arrayValue($result, 'new_user'));
    }
}
