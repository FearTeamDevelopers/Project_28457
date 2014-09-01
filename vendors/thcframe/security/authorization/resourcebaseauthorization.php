<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Core\Base;
use THCFrame\Security\Authorization\AuthorizationInterface;
use THCFrame\Security\UserInterface;

/**
 * Description of ResourceBaseAuthorization
 *
 * @author Tomy
 */
class ResourceBaseAuthorization extends Base implements AuthorizationInterface
{
    /**
     * @read
     * @var type 
     */
    protected $_type = 'resourcebase';
    protected $_roleManager;
    protected $_resources = array();
    
    /**
     * 
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
     * 
     * @param type $resource
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
     * Method checks if logged user has required role
     * 
     * @param \THCFrame\Security\UserInterface $user
     * @param type $requiredRole
     */
    public function isGranted($user, $requiredRole)
    {
        if ($user === null) {
            $actualRole = 'role_guest';
        } elseif($user instanceof UserInterface) {
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
