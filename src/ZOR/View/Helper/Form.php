<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Form
 *
 * @author sergej
 */

namespace ZOR\View\Helper;

use Zend\Form\View\Helper\Form as ZForm;

class Form extends ZForm {

    protected static $model = null;
    protected $element;
    protected $type;

    public function __invoke(\ZOR\ActiveRecord\ActiveRecord $model = null) {
        if (!empty($model)) {
            static::$model = $model;
        }

        return $this;
    }

    public function openTag($method = null, $action = null) {
        $attributes = array();
        $attributes['method'] = (!empty($method)) ? $method : 'POST';
        $attributes['action'] = $action;

        return sprintf('<form %s>', $this->createAttributesString($attributes));
    }

    public function __call($name, $arg = array()) {
        $this->type = $name;
        $className = "Zend\Form\Element\\" . $name;
        $modelName = strtolower(@end(explode("\\", get_class(static::$model))) . "[" . $arg[0] . "]");
        $this->element = new $className($modelName);

        if (!empty($arg[1])) {
            $this->element->setOptions($arg[1]);
        }

        $array = ['Submit', 'Reset'];
        $valueFromModel = (static::$model->offsetExists($arg[0])) ? static::$model->{$arg[0]} : "";
        $value = (in_array($name, $array)) ? $arg[0] : $valueFromModel;

        $this->element->setValue($value);
        $this->element->setMessages(static::$model->getMessages());

        return $this;
    }

    public function __toString() {
        $helper = "form" . $this->type;
        return $this->getView()->$helper($this->element);
    }

}
