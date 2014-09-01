<?php

namespace THCFrame\Security;

/**
 *
 * @author Tomy
 */
interface SecurityInterface
{
    public function createCsrfToken();
    
    public function checkCsrfToken($postToken);
    
    public function initialize();
    
    public function createSalt();
    
    public function getSaltedHash($value, $salt);
    
    public function isGranted($requiredRole);
    
    public function authenticate($name, $pass);
    
}
