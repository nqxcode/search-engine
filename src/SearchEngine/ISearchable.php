<?php
namespace SearchEngine;

/**
 * Interface ISearchable
 *
 * Данный интерфейс должны реализовывать классы моделей, подлежащих индексации
 *
 * @package Search
 */
interface ISearchable
{
    /**
     * Получить идентификатор экземпляра модели
     *
     * @param int
     * @return int
     */
    public function getId();

    /**
     * Поиск экземпляра модели по идентификатору
     *
     * @param int $id
     * @return self
     */
    public static function find($id);

    /**
     * Получить список всех экземпляров модели
     *
     * @return self[]
     */
    public static function getAll();

    /**
     * Доступна ли модель для индексации?
     *
     * @return bool
     */
    public function isAvailableForIndexing();

    /**
     * Получить список пар `название поля` - `значение` для индексируемых полей модели
     *
     * @return Attribute[]
     */
    public function getAttributesForIndexing();
}
