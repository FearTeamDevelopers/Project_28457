<?php

namespace THCFrame\Security;

/**
 *
 */
interface SecurityInterface
{
    
    public function initialize($configuration);
    
    public function isGranted($requiredRole);
    
    public function authenticate($name, $pass);
    
}
