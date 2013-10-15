<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Andrey
 * Date: 17.09.13
 * Time: 10:33
 * ModelFilter
 */

namespace SearchEngine\Result\Filter;

use SearchEngine\ISearchable;
use Symfony\Component\HttpFoundation\Request;
use ZendSearch\Lucene\Search\QueryHit;

/**
 * Class ModelFilter
 *
 * Фильтр результата поискового запроса по моделям.
 *
 * @package Search\Result\Filter
 */
class ModelFilter
{
    /**
     * @var string[]
     */
    protected $allowableClasses = array();

    /**
     * @param $modelClasses Список с названиями классов моделей, по которым будет производиться поиск.
     * Если список пуст, фильтрация производиться не будет
     */
    public function __construct(array $modelClasses = array())
    {
        $this->allowableClasses = $modelClasses;
    }

    /**
     * @param QueryHit[] $itemList
     * @return QueryHit[]
     */
    public function doFilter($itemList)
    {
        $allowableClassList = $this->allowableClasses;
        if (count($this->allowableClasses)) {
            $itemList = array_filter(
                $itemList,
                function (QueryHit $item) use ($allowableClassList) {
                    $class = $item->model;

                    $allowableClassList = array_filter(
                        $allowableClassList,
                        function ($className) {

                            $result = false;
                            if (class_exists($className, true)) {
                                $item = new $className();
                                $result = $item instanceof ISearchable;
                            }

                            return $result;
                        }
                    );

                    $result = in_array($class, $allowableClassList);
                    return $result;
                }
            );
        }

        return $itemList;
    }

}
