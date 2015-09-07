<?php
/**
 * Definition of LoginNotificatorInterface
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\RequestHelper;

/**
 * Interface LoginNotificatorInterface
 */
interface LoginNotificatorInterface
{
    /**
     * Is invoked from Authenticator when a user requested an activation link.
     * This could be when a new user registers or when the user doesn't know his/her password.
     * The implementation should send an e-mail with this link and may update the user's representation in the
     * database.
     *
     * @param UserInterface $user
     * @param string $code
     * @param string $link
     * @param RequestHelper $request
     */
    public function sendActivation(UserInterface $user, $code, $link, RequestHelper $request);

    /**
     * This method is invoked from Authenticator if a valid activation link has been called.
     *
     * @param UserInterface $user
     */
    public function activateUser(UserInterface $user);
}
