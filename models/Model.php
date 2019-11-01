<?php

namespace models;

/**
 * Class Model
 * @package models
 */
class Model
{
    /**
     * Текст свойства, если оно отсутствует в отображении свойств
     */
    const UNDEFINED_TEXT = 'UNDEFINED';

    /**
     * Наименование
     */
    const NAME_FIELD = 'наименование';

    /**
     * идентификатор записи
     */
    const ID_FIELD = 'id';

    /**
     * Основные свойства
     */
    const MAIN_PROPERTY_MAP = [
        self::ID_FIELD => 0,
    ];

    /**
     * Карта свойств модели
     * @var array
     */
    protected $propertyMap;

    /**
     * Значение свойств модели
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Родительская модель
     * @var null|Model
     */
    protected $parent = null;

    /**
     * Наименование поля идентификатора родительской записи
     * @var null|mixed
     */
    protected $parentIdName = null;

    /**
     * Подчинённые модели
     * @var Model[]
     */
    protected $children = [];

    /**
     * Свойство модели, которое означает шаблон отображения
     * @var null|string
     */
    protected $templateProperty = null;

    /**
     * Уровень вложенности в дереве
     * @var int
     */
    protected $level = 0;

    /**
     * Model constructor.
     *
     * @param int|null $id
     */
    public function __construct(array $propertyMap = self::MAIN_PROPERTY_MAP)
    {
        $this->propertyMap = $propertyMap;
    }

    /**
     * @return array
     */
    public function getPropertyMap()
    {
        return $this->propertyMap;
    }

    /**
     * @param array $propertyMap
     * @param bool  $checkOriginalStructure
     *
     * @return Model|null
     */
    public function setPropertyMap(array $propertyMap, bool $checkOriginalStructure = true): Model
    {
        if (!$checkOriginalStructure) {
            $this->propertyMap = $propertyMap;

            return $this;
        }

        $fullArray = array_merge($this->propertyMap, $propertyMap);

        $mergedMap = [];

        foreach ($fullArray as $key => $item) {
            if (isset($this->propertyMap[$key]) && $this->propertyMap[$key] != $item) {
                return null;
            }
            $mergedMap[$key] = $item;
        }

        $this->propertyMap = $mergedMap;

        $mergedValues = [];

        foreach ($this->properties as $key => $property) {
            if (isset($this->propertyMap[$key])) {
                $mergedValues[$property] = $this->properties[$key] ?? null;
            }
        }
        $this->properties = $mergedValues;

        return $this;
    }

    /**
     * @param array $propertyMap
     *
     * @return Model
     */
    public function addPropertyMap(array $propertyMap): Model
    {
        $this->propertyMap = array_merge($this->propertyMap, $propertyMap);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getPropertyIndex(string $name)
    {
        return $this->propertyMap[$name] ?? null;
    }

    /**
     * @param int $index
     *
     * @return |null
     */
    public function getPropertyName(int $index)
    {
        $tmp = array_flip($this->propertyMap);

        return $tmp[$index] ?? null;
    }

    /**
     * @return int
     */
    public function getPropertyCount()
    {
        return count($this->properties ?? []);
    }

    /**
     * Выбираем значение свойства
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function getProperty(string $name)
    {
        $index = $this->getPropertyIndex($name);

        return $this->properties[$index] ?? null;
    }

    /**
     * @param int $index
     *
     * @return mixed|null
     */
    public function getPropertyByIndex(int $index)
    {
        return $this->properties[$index] ?? null;
    }

    /**
     * @param int  $index
     * @param null $value
     *
     * @return $this
     */
    public function setPropertyByIndex(int $index, $value = null)
    {
        if (isset($value)) {
            $this->properties[$index] = $value;
        } else {
            unset($this->properties[$index]);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param null   $value
     *
     * @return $this
     */
    public function setProperty(string $name, $value = null)
    {
        $index = $this->getPropertyIndex($name);

        if (isset($index)) {
            if (isset($value)) {
                $this->properties[$index] = $value;
            } else {
                unset($this->properties[$index]);
            }
        }

        return $this;

    }

    /**
     * @return Model|null
     */
    public function getParent(): ?Model
    {
        return $this->parent;
    }

    /**
     * @param Model|null $parent
     *
     * @return $this
     */
    public function setParent(Model $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setChildren(array $children)
    {
        $this->children = $children;

        return $this;
    }

    public function hasChildren()
    {
        return count($this->children) != 0;
    }

    /**
     * @param $child
     *
     * @return $this
     */
    public function addChild($child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTemplateProperty(): ?string
    {
        if ((!isset($this->templateProperty) || !strlen($this->templateProperty)) && isset($this->parent)) {
            return $this->parent->getTemplateProperty();
        }

        return $this->templateProperty;
    }

    /**
     * @param      $templateProperty
     * @param bool $inherite
     *
     * @return $this
     */
    public function setTemplateProperty($templateProperty, bool $inherite = false)
    {
        $this->templateProperty = $this->getProperty($templateProperty);

        if ($inherite) {
            foreach ($this->children as $child) {
                $child->setTemplateProperty($this->templateProperty, true);
            }
        }

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data)
    {
        foreach ($data as $index => $datum) {
            $this->setPropertyByIndex($index, $datum);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function parseData(): string
    {
        $result = $this->beforeParse();

        $result .= $this->buildFormattedData();

        if ($this->hasChildren()) {
            $result .= '<ul>';
        }

        /* @var Model $child */
        foreach ($this->getChildren() as $child) {
            $result .= $child->parseData();
        }

        if ($this->hasChildren()) {
            $result .= '</ul>';
        }

        $result .= $this->afterParse();

        return $result;
    }

    /**
     * @return mixed|null
     */
    public function getId()
    {
        return $this->getProperty(static::ID_FIELD);
    }

    /**
     * Выборка идентификатора родителя
     *
     * @return mixed|null
     */
    public function getParentId()
    {
        return $this->getProperty($this->parentIdName);
    }

    /**
     * @param string $parentIdName
     */
    public function setParentIdName(string $parentIdName)
    {
        $this->parentIdName = $parentIdName;

        return $this;
    }

    /**
     * @param int|null $level
     *
     * @return $this
     */
    public function setLevel(int $level = null)
    {
        if (isset($level)) {
            $this->level = $level;
        } else {
            if (isset($this->parent)) {
                $this->level = $this->parent->getLevel() + 1;
            } else {
                $this->level = 0;
            }
        }

        foreach ($this->children as $child) {
            $child->setLevel($this->level + 1);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @return string
     */
    protected function beforeParse(): string
    {
        return '';
    }

    /**
     * @return string
     */
    protected function afterParse(): string
    {
        return '';
    }

    /**
     * @param string $search
     * @param string $replace
     * @param string $string
     *
     * @return mixed
     */
    protected function strReplace(string $search, string $replace, string $string)
    {
        $charset = mb_detect_encoding($string);

        $unicodeString = iconv($charset, "UTF-8", $string);

        return str_replace($search, $replace, $unicodeString);
    }

    /**
     * @return string
     */
    protected function buildFormattedData(): string
    {
        $template = $this->getTemplateProperty();

        preg_match_all('/%(.*?)%/', $template, $found);

        $replaces = [];

        foreach ($found[1] as $index => $propertyName) {
            $replaces[$found[0][$index]] = $this->getProperty($propertyName) ?? static::UNDEFINED_TEXT;
        }

        foreach ($replaces as $key => $replace) {
            $template = $this->strReplace($key, $replace, $template);
        }

        return $template;
    }
}
