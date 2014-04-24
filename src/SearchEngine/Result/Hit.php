<?php
namespace SearchEngine\Result;

use SearchEngine\ISearchable;
use ZendSearch\Lucene\Search\QueryHit;

/**
 * Class Hit
 *
 * Описывает элемент результата поиска
 *
 * @package SearchEngine\Result
 */
class Hit
{
    /**
     * @var ISearchable
     */
    private $item;
    /**
     * @var float
     */
    private $score;


    /**
     * @param ISearchable $item
     * @param float $score
     */
    protected function __construct(ISearchable $item, $score)
    {
        $this->item = $item;
        $this->score = $score;
    }

    /**
     * Получить score для hit
     *
     * @return float
     */
    public function getScore()
    {
        return (float)$this->score;
    }

    /**
     * Получить модель
     *
     * @return ISearchable
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Создать объект класса Hit
     *
     * @param QueryHit $hit
     * @return Hit|null
     */
    public static function create(QueryHit $hit)
    {
        $result = null;

        $class = $hit->model;

        if (class_exists($class, true)) {
            $item = new $class();
            if ($item instanceof ISearchable) {
                $item = $item::find($hit->pk);
                if (!is_null($item) && $item->isAvailableForIndexing()) {
                    $result = new Hit($item, $hit->score);
                }
            }
        }

        return $result;
    }
}
