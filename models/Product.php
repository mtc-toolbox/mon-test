<?php

namespace models;

/**
 * Class Product
 * @package models
 */
class Product extends Model
{
    const PRODUCT_PARENT_FIELD = 'категория';

    const PRODUCT_PROPERTY_MAP = [
        self::PRODUCT_PARENT_FIELD => 1,
        self::NAME_FIELD           => 2,
        'цена'                     => 3,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->addPropertyMap(static::PRODUCT_PROPERTY_MAP);

        $this->setParentIdName(static::PRODUCT_PARENT_FIELD);
    }

    /**
     * @return string
     */
    protected function beforeParse(): string
    {
        $res = parent::beforeParse();

        $res .= "<li><b>";

        return $res;
    }

    /**
     * @return string
     */
    protected function afterParse(): string
    {
        $res = '</b></li>';

        $res .= parent::afterParse();

        return $res;
    }
}
