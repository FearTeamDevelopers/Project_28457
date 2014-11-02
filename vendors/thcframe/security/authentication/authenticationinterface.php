<?php

namespace THCFrame\Security\Authentication;

/**
 * AuthenticationInterface ensure that every authentication class has authenticate method
 */
interface AuthenticationInterface
{
    public function authenticate($name, $pass);
}
