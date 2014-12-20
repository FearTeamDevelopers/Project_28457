<?php

namespace THCFrame\Security;

/**
 *
 */
interface SecurityInterface
{
    
    public function initialize();
    
    public function isGranted($requiredRole);
    
    public function authenticate($name, $pass);
    
}
