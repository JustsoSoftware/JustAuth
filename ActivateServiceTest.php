<?php
/**
 * Definition of ActivateServiceTest
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\Bootstrap;
use justso\justapi\NotFoundException;
use justso\justapi\testutil\ServiceTestBase;

/**
 * Class ActivateServiceTest
 */
class ActivateServiceTest extends ServiceTestBase
{
    public function testActivation()
    {
        $env = $this->createTestEnvironment(['c' => '12345']);
        $conf = ['environments' => ['test' => [
            'approot' => '/approot',
            'appurl' => 'http://example.com'
        ]]];
        $env->getBootstrap()->setTestConfiguration('/approot', $conf);
        $auth = $this->getMock('\justso\justauth\Authenticator', [], [], '', false);
        $auth->expects($this->once())->method('activate')->with('12345')->willReturn('dest');
        $env->setDICEntry('Authenticator', $auth);
        $service = new Activate($env);
        $service->getAction();
        $this->assertTrue(in_array('Location: http://example.com/dest', $env->getResponseHeader()));
    }

    /**
     * @expectedException \justso\justapi\InvalidParameterException
     */
    public function testWithoutActivationCode()
    {
        $env = $this->createTestEnvironment();
        $service = new Activate($env);
        $service->getAction();
        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    /**
     * @expectedException \justso\justapi\DenyException
     */
    public function testWrongActivationCode()
    {
        $env = $this->createTestEnvironment(['c' => '12345']);
        $auth = $this->getMock('\justso\justauth\Authenticator', [], [], '', false);
        $auth->expects($this->once())->method('activate')->with('12345')->willThrowException(new NotFoundException());
        $env->setDICEntry('Authenticator', $auth);
        $service = new Activate($env);
        $service->getAction();
        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd
}
