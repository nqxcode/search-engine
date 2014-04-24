<?php
namespace SearchEngine;

use SearchEngine\Exception\SearchCanNotConnectException;
use SearchEngine\Result\Hit;
use SearchEngine\Result\Filter;
use SearchEngine\Result\Filter\ModelFilter;
use ZendSearch\Lucene\Analysis\Analyzer\Analyzer;
use ZendSearch\Lucene\Analysis\Analyzer\Common\Utf8Num\CaseInsensitive;
use ZendSearch\Lucene\Analysis\Analyzer\Common\Utf8;
use ZendSearch\Lucene\Analysis\TokenFilter\StopWords;
use ZendSearch\Lucene\Lucene;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Document\Field;
use ZendSearch\Lucene\Index\Term;
use ZendSearch\Lucene\Search\Query;
use ZendSearch\Lucene\Search\Query\MultiTerm;
use ZendSearch\Lucene\Search\QueryHit;
use ZendSearch\Lucene\Search\QueryParser;
use ZendSearch\Lucene\Search\Query\Wildcard;

/**
 * Class Engine
 *
 * `Поисковая машина` на основе Zend Lucene
 *
 * @package Search
 */
class Engine
{
    private $connection = null;
    /**
     * @var string
     */
    private $lastQuery;

    private $defaultAnalyzer = null;
    private $analyzerForHighlighter = null;

    /**
     * @var string[]
     */
    private $modelClasses = array();

    /**
     * @return CaseInsensitive
     */
    private function getDefaultAnalyzer()
    {
        // используем анализатор для кодировки UTF-8 нечувствительный к регистру
        $analyzer = new CaseInsensitive();

        // добавляем к анализатору фильтры стоп-слов и морфологический фильтр

        $stopWordsFilter = new StopWords();
        $stopWordsFilter->loadFromFile(__DIR__ . '/phpmorphy/stop-words/stop-words-russian.txt');
        $analyzer->addFilter($stopWordsFilter);

        $stopWordsFilter = new StopWords();
        $stopWordsFilter->loadFromFile(__DIR__ . '/phpmorphy/stop-words/stop-words-english4.txt');
        $analyzer->addFilter($stopWordsFilter);

        $analyzer->addFilter(new MorphyFilter());

        return $analyzer;
    }

    /**
     * @return CaseInsensitive
     */
    private function getAnalyzerForHighlighter()
    {
        // используем анализатор для кодировки UTF-8 нечувствительный к регистру
        $analyzer = new CaseInsensitive();

        // добавляем к анализатору морфологический фильтр
        $analyzer->addFilter(new MorphyFilter());
        return $analyzer;
    }

    /**
     * Отфильтровать классы моделей
     *
     * @param string[] $modelClasses
     * @return string[]
     */
    private static function filterModelClasses($modelClasses)
    {
        $result = array();

        if (count($modelClasses) > 0) {
            $result = array_filter(
                $modelClasses,
                function ($class) {
                    $result = false;

                    if (class_exists($class, true)) {
                        $item = new $class();
                        $result = $item instanceof ISearchable;
                    }
                    return $result;
                }
            );
        }

        return $result;
    }

    /**
     *
     * Инициализация Zend Lucene
     *
     * @param string[]|string $modelClasses
     * @param string $indexLocation
     * @throws SearchCanNotConnectException
     */
    public function __construct($modelClasses, $indexLocation)
    {
        if (!is_array($modelClasses)) {
            $modelClasses = array($modelClasses);
        }

        $this->modelClasses = $this->filterModelClasses($modelClasses);

        $this->defaultAnalyzer = $this->getDefaultAnalyzer();
        $this->analyzerForHighlighter = $this->getAnalyzerForHighlighter();

        Analyzer::setDefault($this->defaultAnalyzer);
        QueryParser::setDefaultEncoding('utf-8');

        // сделаем ограничение количества записей результата поиска
        // Lucene::setResultSetLimit(10000);

        // открываем/создаём новый индекс
        if (file_exists($indexLocation = ($indexLocation))) {
            try {
                $this->connection = Lucene::open($indexLocation);
            } catch (\Exception $ex) {
                $this->connection = Lucene::create($indexLocation);
            }
        } else {
            $this->connection = Lucene::create($indexLocation);
        }
        if (!$this->connection) {
            throw new SearchCanNotConnectException($indexLocation);
        }
    }

    /**
     * Удалить из индекса информацию об объекте ISearchable
     *
     * @param ISearchable $item
     */
    public function deleteIndex(ISearchable $item)
    {
        // получаем доступ к индексу
        $index = $this->connection;

        // формируем запрос на удаление существующей записи из индекса
        $query = new MultiTerm();
        $query->addTerm(new Term($item->getId(), 'pk'), true);
        $query->addTerm(new Term(get_class($item), 'model'), true);

        // собственно удаляем
        $hits = $index->find($query);
        foreach ($hits as $hit) {
            $index->delete($hit->id);
        }
    }

    /**
     * Обновить индекс для объекта ISearchable
     *
     * @param ISearchable $item
     */
    public function updateIndex(ISearchable $item)
    {
        // получаем доступ собственно к индексу
        $index = $this->connection;

        // удаляем старый индекс
        $this->deleteIndex($item);

        // недоступные не индексируем
        if (!$item->isAvailableForIndexing()) {
            return;
        }

        $document = new Document();

        // сохраняем первичный ключ модели для идентификации ее в результатах поиска
        $document->addField(Field::Keyword('pk', $item->getId()));

        // id моделей могут пересекаться (например, у продуктов и услуг),
        // поэтому добавим второе поле для однозначной идентификации
        $document->addField(Field::Keyword('model', get_class($item)));

        // индексируем поля модели

        foreach ($item->getAttributesForIndexing() as $attribute) {

            $field = $attribute->getFieldName();
            $value = $attribute->getValue();

            $document->addField(Field::unStored($field, strip_tags($value)));
        }

        // добавляем запись в индекс
        $index->addDocument($document);
        $index->commit();
    }

    /**
     * Сформировать поисковый запрос
     *
     * @param $queryWord
     * @param array $fields
     * @return string
     */
    protected function buildQuery($queryWord, array $fields)
    {
        $queryWord = trim($queryWord);
        $queryStatements = array_map(
            function ($field) use ($queryWord) {
                return "{$field}:\"{$queryWord}\"~100";
            },
            $fields
        );

        return join(' OR ', $queryStatements);
    }


    /**
     * Выполнить поисковый запрос. Результат представляет собой
     *
     * @param string $queryWord строка запроса
     * @param int $totalCount
     * @param string $queryEncoding кодировка строки запроса
     * @param ModelFilter|null $filter фильтр моделей
     * @return Hit[]
     */
    public function search($queryWord, &$totalCount, $queryEncoding = 'utf-8', ModelFilter $filter = null)
    {
        Wildcard::setMinPrefixLength(0);

        $this->lastQuery = mb_convert_encoding($queryWord, 'utf-8', $queryEncoding);

        // фильтрация всех символов, кроме букв и цифр
        $this->lastQuery = mb_ereg_replace("[^а-яА-Яa-zA-Z0-9]+", " ", $this->lastQuery);

        $query = $this->buildQuery($this->lastQuery, $this->getFields());
        $hitList = $this->connection->find($query);

        if (null !== $filter) {
            $hitList = $filter->doFilter($hitList);
        }

        $totalCount = count($hitList);
        return $hitList;
    }


    /**
     * Получить результат для paginator'a
     *
     * @param QueryHit[] $hits
     * @param int $elementsPerPage
     * @param int $currentPage
     * @return Hit[]
     */
    public function parseHitsByRange(array $hits, $elementsPerPage, $currentPage)
    {
        $offset = $currentPage - 1;
        $hits = array_slice($hits, $offset * $elementsPerPage, $elementsPerPage);

        return $this->createResultDataFromHits($hits);
    }


    /**
     * Получить результат поиска целиком
     *
     * @param QueryHit[] $hits
     * @return Result\Hit[]
     */
    public function parseHits(array $hits)
    {
        return $this->createResultDataFromHits($hits);
    }


    /**
     * Получить все поля, по которым будет производиться поиск
     *
     * @return string[]
     */
    protected function getFields()
    {
        $result = array();

        foreach ($this->modelClasses as $class) {
            $item = new $class();
            if ($item instanceof ISearchable) {

                $fields = array_map(function (Attribute $attribute) {
                    return $attribute->getFieldName();

                }, $item->getAttributesForIndexing());

                $result = array_merge($result, $fields);
            }

        }

        $result = array_unique($result);
        return $result;
    }

    /**
     * Получить окончательный результат поиска
     *
     * @param QueryHit[] $hits
     * @return \SearchEngine\Result\Hit[]
     */
    protected function createResultDataFromHits(array $hits)
    {
        $result = array();
        foreach ($hits as $hit) {
            if ($hit instanceof QueryHit) {
                if ($item = Hit::create($hit)) {
                    $result[] = $item;
                }
            }
        }
        return $result;
    }

    /**
     * Глобально обновить поисковый индекс
     */
    public function  fullUpdateIndex()
    {
        foreach ($this->modelClasses as $class) {
            $item = new $class();
            if ($item instanceof ISearchable) {
                $items = $item::getAll();

                foreach ($items as $item) {
                    $this->updateIndex($item);
                }

            }
        }
        $this->commit();
    }

    public function commit()
    {
        $this->connection->optimize();
    }

    /**
     * Подсветка результата поиска в html-фрагменте
     *
     * @param string $inputHTMLFragment исходный фрагмента html
     * @param string $inputEncoding Кодировка исходного фрагмента html
     * @param string $outputEncoding Кодировка резульрирующего фрагмента html
     * @return string html фрагмент с подсвеченными результатами поиска
     */
    public function highlightMatches($inputHTMLFragment, $inputEncoding = 'utf-8', $outputEncoding = 'utf-8')
    {
        $highlightedHTMLFragment = '';

        if (!empty($this->lastQuery)) {
            $queryParser = QueryParser::parse($this->lastQuery);
            /**
             * Убираем фильтры стоп-слов для подсветки слов с псевдокорнями типа 'под' и т.п.
             */
            Analyzer::setDefault($this->analyzerForHighlighter);

            $highlightedHTMLFragment =
                $queryParser->htmlFragmentHighlightMatches($inputHTMLFragment, $inputEncoding, new Highlighter());

            Analyzer::setDefault($this->defaultAnalyzer);

            $highlightedHTMLFragment = mb_convert_encoding($highlightedHTMLFragment, $outputEncoding, 'utf-8');
        }

        return $highlightedHTMLFragment;
    }
}
