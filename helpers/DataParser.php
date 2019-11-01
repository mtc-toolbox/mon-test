<?php

namespace helpers;

use models\Product;
use models\Category;

/**
 * Class DataParser
 * @package helpers
 */
class DataParser
{
    const CSV_DELIMITER = ';';

    const ERROR_CODE_NONE       = 0;
    const ERROR_CODE_FILE_OPEN  = 1;
    const ERROR_CODE_FILE_PARSE = 2;
    const ERROR_CODE_FILE_SAVE  = 3;

    const ERROR_INVALID_FORMAT         = 'Invalid file format';
    const ERROR_INVALID_PRODUCT_HEADER = 'Invalid products header';
    const ERROR_EMPTY_TEXT             = '';

    /**
     * @var string
     */
    protected $categoriesFileName;

    /**
     * @var string
     */
    protected $productsFileName;

    /**
     * @var Product[]
     */
    protected $poductsList = [];

    /**
     * @var Category[]
     */
    protected $categoriesList = [];

    /**
     * @var string
     */
    protected $errorText = '';

    /**
     * @var int
     */
    protected $errorCode = self::ERROR_CODE_NONE;

    /**
     * DataParser constructor.
     *
     * @param string $categories
     * @param string $products
     */
    public function __construct(string $categories, string $products)
    {
        $this->setCategoriesFileName($categories)
            ->setProductsFileName($products);
    }

    /**
     * @return string
     */
    public function getProductsFileName(): string
    {
        return $this->productsFileName;
    }

    /**
     * @param string $productsFileName
     *
     * @return $this
     */
    public function setProductsFileName(string $productsFileName)
    {
        $this->productsFileName = $productsFileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getCategoriesFileName(): string
    {
        return $this->categoriesFileName;
    }

    /**
     * @param string $categoriesFileName
     *
     * @return $this
     */
    public function setCategoriesFileName(string $categoriesFileName)
    {
        $this->categoriesFileName = $categoriesFileName;

        return $this;
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorText(): string
    {
        return $this->errorText;
    }

    /**
     * @param int $errorCode
     *
     * @return $this
     */
    public function setErrorCode(int $errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * @param string $errorText
     *
     * @return $this
     */
    public function setErrorText(string $errorText)
    {
        $this->errorText = $errorText;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->getErrorCode() != static::ERROR_CODE_NONE;
    }

    /**
     * @param int    $code
     * @param string $text
     *
     * @return $this
     */
    public function setError(int $code, string $text)
    {
        $this->setErrorCode($code)
            ->setErrorText($text);

        return $this;
    }

    /**
     * @return $this
     */
    public function clearError()
    {
        $this->setError(static::ERROR_CODE_NONE, static::ERROR_EMPTY_TEXT);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return \SplFileObject|null
     */
    protected function openFile(string $name)
    {

        $obj = null;
        $this->clearError();
        try {
            $obj = new \SplFileObject($name);
        } catch (\Exception $e) {
            $this->setError(static::ERROR_CODE_FILE_OPEN, $e->getMessage());
        }

        return $obj;
    }

    /**
     * @return bool
     */
    public function parseData()
    {
        if (!$this->loadCategories()) {
            return false;
        }

        if (!$this->loadProducts()) {
            return false;
        }

        $this->linkProducts();
        $this->linkCategories();

        return true;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        $res = count($this->categoriesList) ? '<ul>' : '';
        foreach ($this->categoriesList as $item) {
            if ($item->getLevel() > 1) {
                continue;
            }
            $res .= $item->parseData();
        }
        $res .= count($this->categoriesList) ? '</ul>' : '';

        return $res;
    }

    public function flushResult($fileName): bool
    {
        $result = false;

        try {

            $file = new \SplFileObject($fileName, 'w');

            $data = $this->getResult();

            $file->fwrite($data);

            $file->fflush();

            unset($file);

            $result = true;

        } catch (\Exception $exception) {
            $this->setError(static::ERROR_CODE_FILE_SAVE, $exception->getMessage());
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function loadCategories()
    {

        $file = $this->openFile($this->getCategoriesFileName());

        if ($this->hasError()) {
            return false;
        }

        if ($file->eof()) {
            return true;
        }

        $result = false;

        try {
            $file->fgetcsv(static::CSV_DELIMITER);

            while (!$file->eof()) {
                $row = $file->fgetcsv(static::CSV_DELIMITER);
                if ($row === false) {
                    throw new \Exception(static::ERROR_INVALID_FORMAT);
                }
                $category = new Category();
                $category->setData($row);
                $this->categoriesList[$category->getId()] = $category;

            }

            $result = true;

        } catch (\Exception $e) {
            $this->setError(static::ERROR_CODE_FILE_PARSE, $e->getMessage());
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function loadProducts()
    {
        $file = $this->openFile($this->getProductsFileName());

        if ($this->hasError()) {
            return false;
        }

        if ($file->eof()) {
            return true;
        }

        $result = false;

        try {
            $header = $file->fgetcsv(static::CSV_DELIMITER);

            if ($header === false) {
                throw new \Exception(static::ERROR_INVALID_PRODUCT_HEADER);
            }

            $header = array_flip($header);

            while (!$file->eof()) {
                $row = $file->fgetcsv(static::CSV_DELIMITER);
                if ($row === false) {
                    throw new \Exception(static::ERROR_INVALID_FORMAT);
                }
                $product = new Product();
                $product->setPropertyMap($header);
                $product->setData($row);
                $this->poductsList[$product->getId()] = $product;
            }

            $result = true;

        } catch (\Exception $e) {
            $this->setError(static::ERROR_CODE_FILE_PARSE, $e->getMessage());
        }

        return $result;
    }

    /**
     * Присоединение продуктов к категориям
     *
     * @return $this
     */
    protected function linkProducts()
    {
        foreach ($this->poductsList as $item) {
            $categoryIndex = $item->getParentId();
            if (isset($this->categoriesList[$categoryIndex])) {
                $this->categoriesList[$categoryIndex]->addChild($item);
                $item->setParent($this->categoriesList[$categoryIndex]);
            }
        }

        return $this;
    }

    /**
     * Перелинковка категорий
     * @return $this
     */
    protected function linkCategories()
    {
        for (; ;) {
            $wasChanged = false;
            foreach ($this->categoriesList as $category) {
                $parentId = $category->getParentId() ?? 0;
                $parent   = $category->getParent();
                if (!isset($parent) && $parentId && isset($this->categoriesList[$parentId])) {
                    $this->categoriesList[$parentId]->addChild($category);
                    $category->setParent($this->categoriesList[$parentId]);
                    $wasChanged = true;
                }
            }
            if (!$wasChanged) {
                break;
            }
        }

        foreach ($this->categoriesList as $category) {
            $parentId = $category->getParentId() ?? 0;
            if (!$parentId) {
                $category->setLevel(1);

                $category->setTemplateProperty(Category::CATEGORY_TEMPLATE_FIELD);

            }
        }

        return $this;

    }
}
