<?php

namespace THCFrame\Security\Authentication;

/**
 *
 * @author Tomy
 */
interface AuthenticationInterface
{
    public function authenticate($name, $pass);
}
