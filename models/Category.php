<?php

namespace models;

class Category extends Model
{
    const CATEGORY_PARENT_FIELD = 'родитель';

    const CATEGORY_TEMPLATE_FIELD = 'формат описания товаров';

    const CATEGORY_INHERITE_FIELD = 'наследовать дочерним';

    const CATEGORY_PROPERTY_MAP = [
        self::NAME_FIELD              => 1,
        self::CATEGORY_PARENT_FIELD   => 2,
        self::CATEGORY_TEMPLATE_FIELD => 3,
        self::CATEGORY_INHERITE_FIELD => 4,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->addPropertyMap(static::CATEGORY_PROPERTY_MAP);

        $this->setParentIdName(static::CATEGORY_PARENT_FIELD);
    }

    /**
     * @param array $data
     *
     * @return Model
     */
    public function setData(array $data)
    {
        $result = parent::setData($data);

        $this->setTemplateProperty(
            static::CATEGORY_TEMPLATE_FIELD,
            $this->getProperty(static::CATEGORY_INHERITE_FIELD) == 1
        );

        return $result;
    }

    /**
     * @return string
     */
    protected function beforeParse(): string
    {
        $res = parent::beforeParse();

        $res .= "<li>";

        return $res;
    }

    protected function buildFormattedData(): string
    {
        $level = $this->getLevel();

        $name = htmlentities($this->getProperty(static::NAME_FIELD) ?? '');

        $result = "<h{$level}>{$name}</h{$level}>";

        return $result;
    }

    /**
     * @return string
     */
    protected function afterParse(): string
    {
        $res = "</li>";

        $res .= parent::afterParse();

        return $res;
    }

}
