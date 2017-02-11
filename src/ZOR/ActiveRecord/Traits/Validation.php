<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZOR\ActiveRecord\Traits;

/**
 * Description of Validation
 *
 * @author sergej
 */
trait Validation {

    /**
     * array('field_name' => array('Validator' => array('param1' => true)));
     * @var array
     */
    protected $validates = array();

    public function isValid() {

        if (!empty($this->validates)) {
            foreach ($this->validates as $field => $validator) {
                foreach ($validator as $name => $args) {

                    $plugins = StaticValidator::getPluginManager();
                    $validator = $plugins->get($name, $args);
                    $status = $validator->isValid($this->data[$field]);

                    if ($status) {
                        return TRUE;
                    } else {
                        $this->messages = array_merge($this->messages, $validator->getMessages());
                        return FALSE;
                    }
                }
            }
        }
    }

}
