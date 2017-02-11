<?php

namespace ZOR\Controller;

use Zend\Mvc\Controller\AbstractActionController as ZendAbstractActionController;
use Zend\Mvc\MvcEvent;

/**
 * Description of AbstractActionController
 *
 * @author sergej
 */
abstract class AbstractActionController extends ZendAbstractActionController {

    //array('func_name' => array(actions))
    protected $before_action = array();

    public function onDispatch(MvcEvent $e) {


        $action = $e->getRouteMatch()->getParam('action');
        if (!empty($this->before_action)) {
            foreach ($this->before_action as $func_name => $actions) {
                if (empty($actions) || in_array($action, $actions)) {
                    $this->runMethod($func_name);
                }
            }
        }
        parent::onDispatch($e);
    }

    private function runMethod($name) {

        if (method_exists($this, $name)) {
            $this->{$name}();
        }
    }

}
