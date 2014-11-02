<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Security\Model\BasicUser;

/**
 * AuthorizationInterface ensure that authorization class will have isGranted method
 */
interface AuthorizationInterface
{
    public function isGranted(BasicUser $user, $requiredRole);
}
