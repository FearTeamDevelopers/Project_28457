<?php

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 *
 * @author Tomy
 */
class App_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'App';

    /**
     * @read
     */
    protected $_observerClass = 'App_Etc_Observer';
    protected $_routes = array(
        array(
            'pattern' => '/login',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'login',
        ),
        array(
            'pattern' => '/logout',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'logout'
        ),
        array(
            'pattern' => '/project',
            'module' => 'app',
            'controller' => 'project',
            'action' => 'index'
        ),
        array(
            'pattern' => '/user',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'index'
        ),
        array(
            'pattern' => '/client',
            'module' => 'app',
            'controller' => 'client',
            'action' => 'index'
        ),
        array(
            'pattern' => '/calendar',
            'module' => 'app',
            'controller' => 'calendar',
            'action' => 'index'
        ),
        array(
            'pattern' => '/file',
            'module' => 'app',
            'controller' => 'file',
            'action' => 'index'
        ),
        array(
            'pattern' => '/system',
            'module' => 'app',
            'controller' => 'system',
            'action' => 'index'
        ),
        array(
            'pattern' => '/project/:urlkey/',
            'module' => 'app',
            'controller' => 'project',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/task/:urlkey/',
            'module' => 'app',
            'controller' => 'task',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/project/unassignuser/:projectId/:userId',
            'module' => 'app',
            'controller' => 'project',
            'action' => 'unassignUser',
            'args' => array(':projectId', ':userId')
        ),
        array(
            'pattern' => '/project/setstate/:projectId/:state',
            'module' => 'app',
            'controller' => 'project',
            'action' => 'setProjectState',
            'args' => array(':projectId', ':state')
        ),
        array(
            'pattern' => '/task/assign/:taskId/:userId',
            'module' => 'app',
            'controller' => 'task',
            'action' => 'assignToUser',
            'args' => array(':taskId', ':userId')
        ),
        array(
            'pattern' => '/task/setstate/:taskId/:state',
            'module' => 'app',
            'controller' => 'task',
            'action' => 'setTaskState',
            'args' => array(':taskId', ':state')
        ),
        array(
            'pattern' => '/task/removesubtask/:taskId/:subTaskId',
            'module' => 'app',
            'controller' => 'task',
            'action' => 'removeSubTask',
            'args' => array(':taskId', ':subTaskId')
        ),
        array(
            'pattern' => '/task/removereltask/:taskId/:relTaskId',
            'module' => 'app',
            'controller' => 'task',
            'action' => 'removeReletedTask',
            'args' => array(':taskId', ':relTaskId')
        )
    );

}