<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Core\Base;
use THCFrame\Security\Authorization\AuthorizationInterface;
use THCFrame\Security\Model\BasicUser;
use THCFrame\Security\Exception;

/**
 * AnnotationBaseAuthorization use method annotation comments to identify minimal
 * required role
 */
class AnnotationBaseAuthorization extends Base implements AuthorizationInterface
{

    /**
     * Authorization type
     * 
     * @read
     * @var type 
     */
    protected $_type = 'annotationbase';
    
    /**
     * RoleManager instance
     * 
     * @read
     * @var \THCFrame\Security\Authorization\RoleManager
     */
    protected $_roleManager;

    /**
     * Object constructor
     * 
     * @param \THCFrame\Security\Authorization\RoleManager $roleManager
     */
    public function __construct(RoleManager $roleManager)
    {
        parent::__construct();

        $this->_roleManager = $roleManager;
    }

    /**
     * Check if logged user has access to the requested resource
     * 
     * @param \THCFrame\Security\UserInterface $user
     * @param string $requiredRole
     * @return boolean
     * @throws Exception\Role
     */
    public function isGranted(BasicUser $user, $requiredRole)
    {
        if ($user === null) {
            $actualRole = 'role_guest';
        } else {
            $actualRole = strtolower($user->getRole());
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
