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
     * Logs in a user with the given access code.
     *
     * @param string $code
     * @return UserInterface
     * @throws NotFoundException
     */
    public function getByAccessCode($code);

    /**
     * Makes the user object persistent.
     */
    public function persist(UserInterface $user);
}
