<?php
namespace SearchEngine\Tests\Models;


use SearchEngine\Attribute;
use SearchEngine\ISearchable;

/**
 * Class Product
 *
 * Тестовая модель, реализующая интерфейс ISearchable
 *
 * @package SearchEngine\Tests\Models
 */
class Product implements ISearchable
{
    protected static $repository = array();

    protected $id;
    private $article;
    private $description;
    private $publish;


    public static function createAndAddToRepository($article, $description, $publish = true)
    {
        $product = new self($article, $description, $publish);
        $id = $product->getId();
        self::$repository[$id] = $product;
    }

    public function __construct($article = null, $description = null, $publish = true)
    {
        $this->id = uniqid();
        $this->article = $article;
        $this->description = $description;
        $this->publish = $publish;
    }

    /**
     * Получить идентификатор экземпляра модели
     *
     * @param int
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Поиск экземпляра модели по идентификатору
     *
     * @param int $id
     *
     * @return self
     */
    public static function find($id)
    {
        return self::$repository[$id];
    }

    /**
     * Получить список всех экземпляров модели
     *
     * @return self[]
     */
    public static function getAll()
    {
        return self::$repository;
    }

    /**
     * Доступна ли модель для индексации?
     *
     * @return bool
     */
    public function isAvailableForIndexing()
    {
        return $this->publish;
    }

    /**
     * Получить список пар `название поля` - `значение` для индексируемых полей модели
     *
     * @return Attribute[]
     */
    public function getAttributesForIndexing()
    {
        return array(
            new Attribute('article', $this->article),
            new Attribute('description', $this->description)
        );
    }
}
