<?php
/**
 * Definition of UserRepositoryInterface
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justauth;

use justso\justapi\NotFoundException;

/**
 * Interface UserRepositoryInterface
 */
interface UserRepositoryInterface
{
    /**
     * Returns the user identified by his id.
     *
     * @param int $id
     * @return UserInterface
     * @throws NotFoundException
     */
    public function getById($id);

    /**
     * Returns the user identified by her e-mail.
     *
     * @param string $email
     * @return UserInterface
     * @throws NotFoundException
     */
    public function getByEmail($email);

    /**
     * @param UserInterface $user
     * @param bool          $status
     */
    public function setLoginStatus(UserInterface $user, $status);

    /**
     * Registers an access code for the given e-mail address.
     * If the e-mail address not yet known, a new user is created.
     *
     * @param string $email
     * @param string $code
     */
    public function setAccessCode($email, $code);

    /**
     * Logs in a user with the given access code.
     *
     * @param string $code
     * @return UserInterface
     * @throws NotFoundException
     */
    public function loginWithCode($code);

    /**
     * Makes the user object persistent.
     */
    public function persist(UserInterface $user);
}
