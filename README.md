Search engine based on Zend Lucene
=======

[![Latest Stable Version](https://poser.pugx.org/nqxcode/search-engine/v/stable.png)](https://packagist.org/packages/nqxcode/search-engine)
[![Total Downloads](https://poser.pugx.org/nqxcode/search-engine/downloads.png)](https://packagist.org/packages/nqxcode/search-engine)
[![Latest Unstable Version](https://poser.pugx.org/nqxcode/search-engine/v/unstable.png)](https://packagist.org/packages/nqxcode/search-engine)
[![License](https://poser.pugx.org/nqxcode/search-engine/license.png)](https://packagist.org/packages/nqxcode/search-engine)

Gives opportunity of indexing of models (ActiveRecord) on demanded fields.

In order that the model was available to indexation, it is necessary:

1. Add name of class of model to list of model classes at initialization search engine.
2. Declare model with `ISearchable` interface;

## Initialization on search engine
    $searchEngine = new SearchEngine\Engine('\SearchEngine\Tests\Models\Product', $indexDirectory); // $indexDirectory path to index directory

## Declare model with ISearchable interface

    use SearchEngine\ISearchable;

    class Product implements ISearchable
    {
        // TODO ...
    }
### Example of realization `getAttributesForIndexing` method of `ISearchable` interface
    use SearchEngine\ISearchable;

    class Product implements ISearchable
    {
        // ...

        public funtion getAttributesForIndexing()
        {
            // list of couples "field name - field value"
            return array(
                new Attribute('fieldName', $this->fieldName),
                new Attribute('otherFieldName', $this->otherFieldName)
            );
        }
    }

## Operation on index
### Full update for search index
    $searchEngine->fullUpdateIndex();

### Update index for `ISearchable` model
    $searchEngine->updateIndex($model)

### Delete index for `ISearchable` model
    $searchEngine->deleteIndex($model)

## Execute search query
    $queryHits = $searchEngine->search($query);
### Get result for paginator
     $hits = $searchEngine->parseHitsByRange($queryHits, $elementsPerPage, $currentPage);

     // get the found ISearchable model from each $hit
     foreach ($hits as $hit):
         $model = $hit->getItem();
     }

### Get full result
    $hits = $searchEngine->parseHits($queryHits);

     // get the found ISearchable model from each $hit
     foreach ($hits as $hit):
         $model = $hit->getItem();
     }
