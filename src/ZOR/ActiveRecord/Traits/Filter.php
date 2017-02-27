<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZOR\ActiveRecord\Traits;

/**
 * Description of Filter
 *
 * @author sergej
 */
trait Filter {

    /**
     * array('field_name' => array('FilterName' => array('param1' => true)));
     * @var array
     */
    protected $filters = array();

    protected function filterIt($field, $value) {
        if (key_exists($field, $this->filters)) {
            foreach ($this->filters[$field] as $name => $args) {
                return $this->filter($value, $name, $args);
            }
        } else {
            return $value;
        }
    }

    protected function filterAll() {
        if (!empty($this->filters)) {
            foreach ($this->filters as $field => $filter) {
                foreach ($filter as $name => $args) {
                    $this->data[$field] = $this->filter($this->data[$field], $name, $args);
                }
            }
        }
    }

    public function filter($value, $name_of_filter, $args = array()) {
        return StaticFilter::execute($value, $name_of_filter, $args);
    }

}
