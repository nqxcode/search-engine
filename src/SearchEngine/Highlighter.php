<?php
namespace SearchEngine;

use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Search\Highlighter\HighlighterInterface;

/**
 * Class Highlighter
 *
 * Предоставляет фк для подсветки искомых слов
 *
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
     * {@inheritdoc}
     */
    public function setDocument(Document\HTML $document)
    {
        $this->_doc = $document;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocument()
    {
        return $this->_doc;
    }

    /**
     * {@inheritdoc}
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
