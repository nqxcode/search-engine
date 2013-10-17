<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Andrey
 * Date: 27.08.13
 * Time: 15:57
 * To change this template use File | Settings | File Templates.
 */

namespace SearchEngine\Result;

use SearchEngine\ISearchable;
use ZendSearch\Lucene\Search\QueryHit;

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
     * @return float
     */
    public function getScore()
    {
        return (float)$this->score;
    }

    /**
     * @return ISearchable
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
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
