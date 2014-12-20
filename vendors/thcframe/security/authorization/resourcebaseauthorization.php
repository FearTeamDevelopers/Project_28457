<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Core\Base;
use THCFrame\Security\Authorization\AuthorizationInterface;
use THCFrame\Security\Model\BasicUser;

/**
 * ResourceBaseAuthorization use resource and his permissions defined in config file
 */
class ResourceBaseAuthorization extends Base implements AuthorizationInterface
{
    /**
     * Authorization type
     * 
     * @read
     * @var string
     */
    protected $_type = 'resourcebase';
    
    /**
     * RoleManager instance
     * 
     * @read
     * @var \THCFrame\Security\Authorization\RoleManager
     */
    protected $_roleManager;
    
    /**
     *
     * @var array 
     */
    protected $_resources = array();
    
    /**
     * Clean up resource name and role given from config file
     */
    private function normalizeResources()
    {
        $normalizedResources = array();
        
        foreach ($this->_resources as $resource) {
            list($uri, $reqRole) = explode(':', $resource);
            $normalizedResources[trim($uri)] = trim($reqRole);
        }
        
        $this->_resources = $normalizedResources;
    }
    
    /**
     * Object constructor
     * 
     * @param \THCFrame\Security\Authorization\RoleManager $roleManager
     * @param array $resources
     */
    public function __construct(RoleManager $roleManager, array $resources)
    {
        parent::__construct();
        
        $this->_roleManager = $roleManager;
        $this->_resources = $resources;
        
        $this->normalizeResources();
    }
    
    /**
     * Check if required resource exists
     * 
     * @param string $resource
     */
    public function checkForResource($resource)
    {
        $resource = htmlspecialchars($resource);
        
        if(array_key_exists($resource, $this->_resources)){
            return $this->_resources[$resource];
        }else{
            return null;
        }
    }
    
    /**
     * Check if logged user has permission to acces required resource
     * 
     * @param \THCFrame\Security\Model\BasicUser $user
     * @param string $requiredRole
     */
    public function isGranted($user, $requiredRole)
    {
        if ($user === null) {
            $actualRole = 'role_guest';
        } elseif($user instanceof BasicUser) {
            $actualRole = strtolower($user->getRole());
        }else{
            $actualRole = 'role_guest';
        }

        $requiredRole = strtolower(trim($requiredRole));

        if (substr($requiredRole, 0, 5) != 'role_') {
            throw new Exception\Role(sprintf('Role %s is not valid', $requiredRole));
        } elseif (!$this->_roleManager->roleExist($requiredRole)) {
            throw new Exception\Role(sprintf('Role %s is not deffined', $requiredRole));
        } else {
            $actualRoles = $this->_roleManager->getRole($actualRole);

            if (NULL !== $actualRoles) {
                if (in_array($requiredRole, $actualRoles)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new Exception\Role(sprintf('User role %s is not valid role', $actualRole));
            }
        }
    }

}
