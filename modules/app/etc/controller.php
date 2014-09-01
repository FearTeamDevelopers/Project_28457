<?php

namespace App\Etc;

use THCFrame\Events\Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\StringMethods;

/**
 * Module specific controller class extending framework controller class
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    private $_security;

    /**
     * 
     * @param type $string
     * @return type
     */
    protected function _createUrlKey($string)
    {
        $string = StringMethods::removeDiacriticalMarks($string);
        $string = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|', ' '), '-', $string);
        $string = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $string);
        $string = trim($string);
        $string = trim($string, '-');
        return strtolower($string);
    }
    
    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });
    }

    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $user = $this->getUser();

        if (!$user) {
            self::redirect('/login');
        }

        //6h inactivity till logout
        if ($session->get('lastActive') > time() - 300) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('You has been logged out for long inactivity');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * @protected
     */
    public function _client()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_client') !== true) {
            $view->infoMessage('Access denied');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isClient()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_client') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _developer()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_developer') !== true) {
            $view->infoMessage('Access denied');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isDeveloper()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_developer') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _projectmanager()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_projectmanager') !== true) {
            $view->infoMessage('Access denied');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isProjectManager()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_projectmanager') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _admin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_admin') !== true) {
            $view->infoMessage('Access denied');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_admin') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _superadmin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') !== true) {
            $view->infoMessage('Access denied');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isSuperAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $projectId
     * @return boolean
     */
    public function hasAccessToProject($projectId)
    {
        if($this->isSuperAdmin()){
            return true;
        }
        
        if(!is_numeric($projectId)){
            $project = \App_Model_Project::fetchProjectByUrlKey($projectId);
            $projectId = $project->getId();
        }
        
        $projectU = \App_Model_ProjectUser::first(array(
            'projectId = ?' => $projectId,
            'userId = ?' => $this->getUser()->getId()
        ));

        if($projectU !== null){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * load user from security context
     */
    public function getUser()
    {
        $this->_security = Registry::get('security');
        $user = $this->_security->getUser();

        return $user;
    }

    /**
     * 
     */
    public function mutliSubmissionProtectionToken()
    {
        $session = Registry::get('session');
        $token = $session->get('submissionprotection');

        if ($token === null) {
            $token = md5(microtime());
            $session->set('submissionprotection', $token);
        }

        return $token;
    }

    /**
     * 
     * @return type
     */
    public function revalidateMutliSubmissionProtectionToken()
    {
        $session = Registry::get('session');
        $session->erase('submissionprotection');
        $token = md5(microtime());
        $session->set('submissionprotection', $token);
        
        return $token;
    }

    /**
     * 
     * @param type $token
     */
    public function checkMutliSubmissionProtectionToken($token)
    {
        $session = Registry::get('session');
        $sessionToken = $session->get('submissionprotection');

        if ($token == $sessionToken) {
            $session->erase('submissionprotection');
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function checkToken()
    {
        if($this->_security->checkCsrfToken(RequestMethods::post('tk'))){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $user = $this->getUser();

        if ($view) {
            $view->set('authUser', $user);
            $view->set('isClient', $this->isClient())
                    ->set('isDeveloper', $this->isDeveloper())
                    ->set('isPM', $this->isProjectManager())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCsrfToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $user);
            $layoutView->set('isClient', $this->isClient())
                    ->set('isDeveloper', $this->isDeveloper())
                    ->set('isPM', $this->isProjectManager())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCsrfToken());
        }

        parent::render();
    }

}
