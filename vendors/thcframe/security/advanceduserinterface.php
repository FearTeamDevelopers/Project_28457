<?php

namespace THCFrame\Security;

use THCFrame\Security\UserInterface;

/**
 * Description of AdvancedUserInterface
 *
 * @author Tomy
 */
interface AdvancedUserInterface extends UserInterface
{

    public function getPasswordExpire();

    public function setPasswordExpire($value);

    public function getAccountExpire();

    public function setAccountExpire($value);

    public function getLastLogin();

    public function setLastLogin();

    public function isExpired();

    public function isPassExpired();
}
