<?php

namespace THCFrame\Security;

use THCFrame\Core\Base as Base;
use THCFrame\Security\Exception as Exception;

/**
 * Description of RoleManager
 *
 * @author Tomy
 */
class RoleManager extends Base
{

    /**
     * @readwrite
     * @var type 
     */
    protected $_roles;

    /**
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        foreach ($options as $value) {
            $start = strpos($value, '[');
            $end = strpos($value, ']');

            if ($start) {
                $role = substr($value, 0, $start);

                if (strtolower(substr($role, 0, 5)) != 'role_') {
                    throw new Exception\Role('Role name is not valid');
                }

                $extend = substr($value, $start + 1, ($end - $start - 1));
                $extendArr = explode(',', $extend);
                array_unshift($extendArr, $role);

                $trimedExtendArr = array_map('trim', $extendArr);

                $this->_roles[$role] = $trimedExtendArr;
            } else {
                $this->_roles[$value] = array($value);
            }
        }
    }

    /**
     * 
     * @return type
     */
    public function getRoles()
    {
        return $this->_roles;
    }

    /**
     * 
     * @param type $rolename
     * @return null
     */
    public function getRole($rolename)
    {
        if ($this->roleExist($rolename)) {
            return $this->_roles[$rolename];
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $rolename
     * @return boolean
     */
    public function roleExist($rolename)
    {
        if (array_key_exists($rolename, $this->_roles)) {
            return true;
        } else {
            return false;
        }
    }

}
