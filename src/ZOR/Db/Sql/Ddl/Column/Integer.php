<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZOR\Db\Sql\Ddl\Column;

use Zend\Db\Sql\Ddl\Column\Column;

class Integer extends Column {

    /**
     * @return array
     */
    public function getExpressionData() {
        $data = parent::getExpressionData();
        $options = $this->getOptions();

        if (isset($options['length'])) {
            $data[0][1][1] .= '(' . $options['length'] . ')';
        }

        if (isset($options['attribute'])) {
            $data[0][1][1] .= ' ' . $options['attribute'];
        }

        if (isset($options['auto_increment'])) {
            $data[0][1][1] .= ' ' . $options['auto_increment'];
        }

        return $data;
    }

}
