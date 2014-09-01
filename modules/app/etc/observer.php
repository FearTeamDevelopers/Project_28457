<?php

use THCFrame\Registry\Registry;
use THCFrame\Events\SubscriberInterface;

/**
 * Observer class handling events defined in configuration file
 * 
 * @author Tomy
 */
class App_Etc_Observer implements SubscriberInterface
{

    /**
     * 
     * @return type
     */
    public function getSubscribedEvents()
    {
        return array(
            'app.log' => 'adminLog'
        );
    }
    
    /**
     * 
     * @param array $params
     */
    public function adminLog()
    {
        $params = func_get_args();
        
        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $security = Registry::get('security');
        $userId = $security->getUser()->getWholeName();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $result = array_shift($params);
            
            if (!empty($params)) {
                $paramStr = join(', ', $params);
            }
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
