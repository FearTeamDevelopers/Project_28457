<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Core\Base;
use THCFrame\Security\Authorization\AuthorizationInterface;
use THCFrame\Security\UserInterface;
use THCFrame\Security\Exception;

/**
 * Description of AnnotationBaseAuthorization
 *
 * @author Tomy
 */
class AnnotationBaseAuthorization extends Base implements AuthorizationInterface
{

    /**
     * @read
     * @var type 
     */
    protected $_type = 'annotationbase';
    protected $_roleManager;

    /**
     * 
     * @param \THCFrame\Security\Authorization\RoleManager $roleManager
     */
    public function __construct(RoleManager $roleManager)
    {
        parent::__construct();

        $this->_roleManager = $roleManager;
    }

    /**
     * Method checks if logged user has required role
     * 
     * @param \THCFrame\Security\UserInterface $user
     * @param type $requiredRole
     * @return boolean
     * @throws Exception\Role
     */
    public function isGranted(UserInterface $user, $requiredRole)
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
