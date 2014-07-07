<?php

use THCFrame\Registry\Registry as Registry;

/**
 * Observer class handling events defined in configuration file
 * 
 * @author Tomy
 */
class App_Etc_Observer
{

    /**
     * 
     * @param array $params
     */
    public function adminLog($params = array())
    {
        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $security = Registry::get('security');
        $userId = $security->getUser()->getWholeName();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $result = array_shift($params);
            $paramStr = join(', ', $params);
        } else {
            $result = 'fail';
            $paramStr = '';
        }

        $log = new App_Model_AdminLog(array(
            'userId' => $userId,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'result' => $result,
            'params' => $paramStr
        ));

        if ($log->validate()) {
            $log->save();
        }
    }

}
