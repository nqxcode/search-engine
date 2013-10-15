<?php

/*
 * This file is part of SearchEngine.
 */

namespace SearchEngine\Tests;

use SearchEngine;
use  SearchEngine\Tests\Models\Product;

/**
 * SearchEngine Test
 *
 * @package SearchEngine
 * @author  nc101ux <nc101ux@gmail.com>
 *
 * @static  SearchEngine\Engine $engine
 * @static  string $luceneIndexPath
 */

class SearchEngineTest extends \PHPUnit_Framework_TestCase
{
    protected static $tempDir = 'c:/windows/temp';
    /**
     * @var string
     */
    protected static $luceneIndexPath;
    /**
     * @var SearchEngine\Engine $engine
     */
    protected static $backend;

    public static function setUpBeforeClass()
    {
        self::createProducts();

        $tempDir               = self::$tempDir;
        self::$luceneIndexPath = self::$tempDir . '/lucene/';

        if (file_exists($tempDir) && is_writable($tempDir)) {

            $indexDir = self::$luceneIndexPath;

            try {
                self::deleteRecursive(self::$luceneIndexPath);
                mkdir($indexDir);
                self::$backend = new SearchEngine\Engine('\SearchEngine\Tests\Models\Product', $indexDir);
                self::$backend->fullUpdateIndex();

            } catch (SearchEngine\Exception\SearchCanNotConnectException $e) {
                self::markTestSkipped('Couldn\'t open ZendLucene index.');
            }
        } else {
            self::markTestSkipped("Can't get access to '{$tempDir}' directory.");
        }
    }

    public static function deleteRecursive($path)
    {
        if (is_dir($path) === true) {
            $files = array_diff(scandir($path), array('.', '..'));

            foreach ($files as $file) {
                self::deleteRecursive(realpath($path) . '/' . $file);
            }

            return rmdir($path);
        } else {
            if (is_file($path) === true) {
                return unlink($path);
            }
        }

        return false;
    }

    public static function createProducts()
    {
        Product::createAndAddToRepository('товар', 'Часы');
        Product::createAndAddToRepository('услуга', 'Настройка часов');
        Product::createAndAddToRepository('старый товар', 'снят с производства', false);
    }

    public function testSearchByQuery()
    {
        self::$backend->search('телефон', $count);
        $this->assertEquals(0, $count, 'Search result must bee empty');

        self::$backend->search('услуга', $count);
        $this->assertEquals(1, $count, 'No search result');

        self::$backend->search('услуга часы', $count);
    }

    public function testHighlightMatches()
    {
        self::$backend->search('товар', $count);
        $highlightHtml = self::$backend->highlightMatches("Тестовый товар");
        $this->assertEquals(
            $highlightHtml, 'Тестовый <span class="highlight-word"> товар </span>', 'No highlight matches'
        );
    }

    public function testMorphologySearchByQuery()
    {
        self::$backend->search('много товаров', $count);
        $this->assertEquals(1, $count, 'No search result');

        self::$backend->search('товары', $count);
        $this->assertEquals(1, $count, 'No search result');

        self::$backend->search('услуги и товары', $count);
        $this->assertEquals(2, $count, 'No search result');
    }
}
