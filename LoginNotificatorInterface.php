<?php
/**
 * Definition of LoginNotificatorInterface
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

/**
 * Interface LoginNotificatorInterface
 */
interface LoginNotificatorInterface
{
    /**
     * Sends an activation link to the specified user.
     *
     * @param UserInterface $user
     * @param string        $link
     */
    public function sendActivationLink(UserInterface $user, $link);
}
