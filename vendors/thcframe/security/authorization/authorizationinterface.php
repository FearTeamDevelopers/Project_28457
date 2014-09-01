<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Security\UserInterface;
/**
 *
 * @author Tomy
 */
interface AuthorizationInterface
{
    public function isGranted(UserInterface $user, $requiredRole);
}
