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
     * Returns the full name of the user or an empty string if the name is not (yet) known.
     *
     * @return string
     */
    public function getFullName();

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

    /**
     * Returns the access token set for this user or null if there is none.
     *
     * @return string
     */
    public function getToken();

    /**
     * Sets an access token for this user.
     *
     * @param string $code
     */
    public function setToken($code);

    /**
     * Sets the destination for access via the access token.
     *
     * @param string $destination
     */
    public function setDestination($destination);

    /**
     * Returns the destination of an access via the current access token.
     *
     * @return string
     */
    public function getDestination();
}
