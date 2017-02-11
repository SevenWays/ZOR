<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZOR\ActiveRecord\Traits;

/**
 * Description of Predication
 *
 * @author sergej
 */
trait Predication {
   
      /**
     *
     * @var \Zend\Db\Sql\Predicate\Predicate 
     */
    protected $predicate = null;
    
     /**
     * Create "Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function equalTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->equalTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Not Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function notEqualTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->notEqualTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Less Than" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function lessThan($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->lessThan($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Greater Than" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function greaterThan($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->greaterThan($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Less Than Or Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function lessThanOrEqualTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->lessThanOrEqualTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Greater Than Or Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function greaterThanOrEqualTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->greaterThanOrEqualTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Like" predicate
     *
     * Utilizes Like predicate
     *
     * @param  string $identifier
     * @param  string $like
     * @return Predicate
     */
    public function like($identifier, $like) {
        $this->predicate->like($identifier, $like);
        return $this;
    }

    /**
     * Create "notLike" predicate
     *
     * Utilizes In predicate
     *
     * @param  string $identifier
     * @param  string $notLike
     * @return Predicate
     */
    public function notLike($identifier, $notLike) {
        $this->predicate->notLike($identifier, $notLike);
        return $this;
    }

    /**
     * Create an expression, with parameter placeholders
     *
     * @param $expression
     * @param $parameters
     * @return $this
     */
    public function expression($expression, $parameters) {
        $this->predicate->expression($expression, $parameters);
        return $this;
    }

    /**
     * Create "Literal" predicate
     *
     * Literal predicate, for parameters, use expression()
     *
     * @param  string $literal
     * @return Predicate
     */
    public function literal($literal) {
        $this->predicate->literal($literal);
        return $this;
    }

    /**
     * Create "IS NULL" predicate
     *
     * Utilizes IsNull predicate
     *
     * @param  string $identifier
     * @return Predicate
     */
    public function isNull($identifier) {
        $this->predicate->isNull($identifier);
        return $this;
    }

    /**
     * Create "IS NOT NULL" predicate
     *
     * Utilizes IsNotNull predicate
     *
     * @param  string $identifier
     * @return Predicate
     */
    public function isNotNull($identifier) {
        $this->predicate->isNotNull($identifier);
        return $this;
    }

    /**
     * Create "NOT IN" predicate
     *
     * Utilizes NotIn predicate
     *
     * @param  string $identifier
     * @param  array|\Zend\Db\Sql\Select $valueSet
     * @return Predicate
     */
    public function notIn($identifier, $valueSet = null) {
        $this->predicate->notIn($identifier, $valueSet);
        return $this;
    }

    /**
     * Create "IN" predicate
     *
     * Utilizes In predicate
     *
     * @param  string $identifier
     * @param  array|\Zend\Db\Sql\Select $valueSet
     * @return Predicate
     */
    public function in($identifier, $valueSet = null) {
        $this->predicate->in($identifier, $valueSet);
        return $this;
    }

    /**
     * Create "between" predicate
     *
     * Utilizes Between predicate
     *
     * @param  string $identifier
     * @param  int|float|string $minValue
     * @param  int|float|string $maxValue
     * @return \Zend\Db\Sql\Predicate\Predicate
     */
    public function between($identifier, $minValue, $maxValue) {
        $this->predicate->between($identifier, $minValue, $maxValue);
        return $this;
    }

    /**
     * Use given predicate directly
     *
     * @param  PredicateInterface $predicate
     * @return Predicate
     */
    public function predicate(PredicateInterface $predicate) {
        $this->predicate->predicate($predicate);
        return $this;
    }
}
