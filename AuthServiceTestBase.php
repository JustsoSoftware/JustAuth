<?php
/**
 * Definition of AuthServiceTestBase.php
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\testutil\ServiceTestBase;
use justso\justapi\testutil\TestEnvironment;

/**
 * Class AuthServiceTestBase
 */
class AuthServiceTestBase extends ServiceTestBase
{
    /**
     * @param TestEnvironment $env
     * @param int $id
     * @return \PHPUnit_FrameWork_MockObject_MockObject
     */
    protected function getMockUser(TestEnvironment $env, $id)
    {
        $user = $this->mockInterface('\\justso\\justauth', 'UserInterface', $env);
        $user->expects($this->any())->method('getId')->willReturn($id);
        return $user;
    }

    /**
     * @param TestEnvironment $env
     * @param UserInterface $user
     */
    protected function setAuthenticatedUser(TestEnvironment $env, UserInterface $user)
    {
        $env->setDICEntry('Authenticator', 'justso\justauth\EMailAuthenticator');
        $env->getSession()->setValue('user', $user);
    }
}
