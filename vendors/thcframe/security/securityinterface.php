<?php

namespace THCFrame\Security;

/**
 *
 * @author Tomy
 */
interface SecurityInterface
{
    
    public function initialize();
    
    public function isGranted($requiredRole);
    
    public function authenticate($name, $pass);
    
}
