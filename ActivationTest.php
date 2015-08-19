<?php
/**
 * Definition of Activate Service
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
use justso\justapi\RequestHelper;
use justso\justapi\testutil\ServiceTestBase;
use justso\justapi\testutil\TestEnvironment;

class ActivationTest extends ServiceTestBase
{
    public function testActivation()
    {
        $code = '0123456789ABCDEF0123456789ABCDEF';
        $env = $this->callTest($code, ['activationCode' => $code]);
        $this->assertSame(array('Location: http://localhost/'), $env->getResponseHeader());
    }

    /**
     * Test with an activation code differing from the one stored in the session.
     *
     * @expectedException \justso\justapi\DenyException
     */
    public function testActivationWithInvalidCode()
    {
        $this->callTest('0123456789ABCDEF0123456789ABCDEF', []);
    }

    /**
     * Test redirection to a specific page which is stored in the database
     */
    public function testRedirectionToPage()
    {
        $code = '0123456789ABCDEF0123456789ABCDEF';
        $env = $this->callTest($code, ['activationCode' => $code, 'currentPage' => 'redirected']);
        $this->assertSame(array('Location: http://localhost/redirected'), $env->getResponseHeader());
    }

    /**
     * @return TestEnvironment
     */
    private function callTest($code, $sessionData)
    {
        $request = new RequestHelper();
        $request->fillWithData(['c' => $code], ['HTTP_USER_AGENT' => 'PHPUnit', 'REMOTE_ADDR' => '127.0.0.1']);
        $env = new TestEnvironment($request);

        $this->setSessionCode($sessionData, $env);
        $this->setDIC($env);

        $session = $env->getSession();
        foreach ($sessionData as $key => $value) {
            $session->setValue($key, $value);
        }
        $service = new Activate($env);
        $service->getAction();
        return $env;
    }

    /**
     * @param string[]        $sessionData
     * @param TestEnvironment $env
     */
    private function setSessionCode($sessionData, TestEnvironment $env)
    {
        $config = ['environments' => ['test' => ['approot' => '/test']]];
        Bootstrap::getInstance()->setTestConfiguration('/test', $config);
        if (isset($sessionData['activationCode'])) {
            $fs = $env->getFileSystem();
            $content = json_encode([$sessionData['activationCode'] => 'test@example.com']);
            $fs->putFile('/test/files/accesscodes.json', $content);
        }
    }

    /**
     * @param TestEnvironment $env
     */
    private function setDIC(TestEnvironment $env)
    {
        $env->setDICEntry('UserInterface', '\justso\innolab\User');
        $env->setDICEntry('UserRepositoryInterface', '\justso\innolab\UserRepository');
        $env->setDICEntry('UserActivatorInterface', '\justso\innolab\UserActivator');
    }
}
