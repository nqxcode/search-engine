<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Andrey
 * Date: 05.09.13
 * Time: 15:00
 * Highlighter
 */

namespace SearchEngine;

use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Search\Highlighter\HighlighterInterface;

/**
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 */
class Highlighter implements HighlighterInterface
{
    /**
     * HTML document for highlighting
     *
     * @var \ZendSearch\Lucene\Document\HTML
     */
    protected $_doc;

    /**
     * Set document for highlighting.
     *
     * @param \ZendSearch\Lucene\Document\HTML $document
     */
    public function setDocument(Document\HTML $document)
    {
        $this->_doc = $document;
    }

    /**
     * Get document for highlighting.
     *
     * @return \ZendSearch\Lucene\Document\HTML $document
     */
    public function getDocument()
    {
        return $this->_doc;
    }

    /**
     * Highlight specified words
     *
     * @param string|array $words  Words to highlight. They could be organized using the array or string.
     */
    public function highlight($words)
    {
        $this->_doc->highlightExtended($words, array($this, 'applyColour'), array());
    }

    public function applyColour($stringToHighlight)
    {
        return "<span class='highlight-word'>{$stringToHighlight}</span>";
    }
}
