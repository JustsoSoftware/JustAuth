<?php
/**
 * Definition of UserInterface
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */


namespace justso\justauth;

use justso\justapi\RequestHelper;

/**
 * Interface UserInterface
 */
interface UserInterface
{
    /**
     * Sets user data from request.
     *
     * @param RequestHelper $request
     */
    public function setFromRequest(RequestHelper $request);

    /**
     * Returns the id of the user.
     *
     * @return int
     */
    public function getId();

    /**
     * Returns the e-mail address of the user.
     *
     * @return string
     */
    public function getEMail();

    /**
     * @return boolean
     */
    public function isActive();

    /**
     * @param boolean $active
     */
    public function setActive($active);

    /**
     * Checks if the password is correct
     *
     * @param $password
     * @return bool
     */
    public function checkPassword($password);
}
