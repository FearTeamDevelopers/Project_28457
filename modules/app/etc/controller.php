<?php

namespace App\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;

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
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $database = Registry::get('database');
        $database->connect();

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
        $lastActive = $session->get('lastActive');

        $user = $this->getUser();

        if (!$user) {
            self::redirect('/login');
        }

        //6h inactivity till logout
        if ($lastActive > time() - 21600) {
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

        if ($this->_security->getUser() && !$this->_security->isGranted('role_client')) {
            $view->infoMessage('Access denied! Client access level required.');
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
        if ($this->_security->getUser() && $this->_security->isGranted('role_client')) {
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

        if ($this->_security->getUser() && !$this->_security->isGranted('role_developer')) {
            $view->infoMessage('Access denied! Developer access level required.');
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
        if ($this->_security->getUser() && $this->_security->isGranted('role_developer')) {
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

        if ($this->_security->getUser() && !$this->_security->isGranted('role_projectmanager')) {
            $view->infoMessage('Access denied! Project manager access level required.');
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
        if ($this->_security->getUser() && $this->_security->isGranted('role_projectmanager')) {
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

        if ($this->_security->getUser() && !$this->_security->isGranted('role_admin')) {
            $view->infoMessage('Access denied! Administrator access level required.');
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
        if ($this->_security->getUser() && $this->_security->isGranted('role_admin')) {
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

        if ($this->_security->getUser() && !$this->_security->isGranted('role_superadmin')) {
            $view->infoMessage('Access denied! Super admin access level required.');
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
        if ($this->_security->getUser() && !$this->_security->isGranted('role_superadmin')) {
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
    public function checkToken()
    {
        $session = Registry::get('session');
        //$security = Registry::get('security');
        $view = $this->getActionView();

        if (base64_decode(RequestMethods::post('tk')) !== $session->get('csrftoken')) {
            $view->errorMessage('Security token is not valid');
            //$security->logout();
            self::redirect('/');
        }
    }
    
    /**
     * 
     * @return boolean
     */
    public function checkTokenAjax()
    {
        $session = Registry::get('session');

        if (base64_decode(RequestMethods::post('tk')) === $session->get('csrftoken')) {
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
            $view->set('isClient', $this->_security->isGranted('role_client'))
                    ->set('isDeveloper', $this->_security->isGranted('role_developer'))
                    ->set('isPM', $this->_security->isGranted('role_projectmanager'))
                    ->set('isAdmin', $this->_security->isGranted('role_admin'))
                    ->set('isSuperAdmin', $this->_security->isGranted('role_superadmin'))
                    ->set('token', $this->_security->getCsrfToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $user);
            $layoutView->set('isClient', $this->_security->isGranted('role_client'))
                    ->set('isDeveloper', $this->_security->isGranted('role_developer'))
                    ->set('isPM', $this->_security->isGranted('role_projectmanager'))
                    ->set('isAdmin', $this->_security->isGranted('role_admin'))
                    ->set('isSuperAdmin', $this->_security->isGranted('role_superadmin'))
                    ->set('token', $this->_security->getCsrfToken());
        }

        parent::render();
    }

}
