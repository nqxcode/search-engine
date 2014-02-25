<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Andrey
 * Date: 26.08.13
 * Time: 17:34
 * MorphyFilter
 */

namespace SearchEngine;

use ZendSearch\Lucene\Analysis\Token;
use ZendSearch\Lucene\Analysis\TokenFilter\TokenFilterInterface;

class MorphyFilter implements TokenFilterInterface
{
    /**
     * @var \phpMorphy[]
     */
    protected $morphy;

    protected $directory;
    protected $language;
    protected $options;

    /**
     * Минимальная длина лексемы
     *
     * @var int
     */
    const MIN_TOKEN_LENGTH = 1;

    protected $dictionaryEncoding = null;

    public function __construct()
    {
        $configList = array();
        require_once __DIR__ . '/phpmorphy/src/common.php';

        $config = new \stdClass();
        $config->directory = __DIR__ . '/phpmorphy/dicts/ru/windows-1251'; // путь к каталогу со словарями
        $config->language = 'ru_RU'; // указываем, для какого языка будем использовать словарь.
        $config->options = array(
            'storage' => PHPMORPHY_STORAGE_FILE,
            'predict_by_suffix' => true,
            'predict_by_db' => true
        );

        $configList[Language::RU] = $config;

        $config = new \stdClass();
        $config->directory = __DIR__ . '/phpmorphy/dicts/en/windows-1250'; // путь к каталогу со словарями
        $config->language = 'en_EN'; // указываем, для какого языка будем использовать словарь.
        $config->options = array(
            'storage' => PHPMORPHY_STORAGE_FILE,
            'predict_by_suffix' => true,
            'predict_by_db' => true
        );

        $configList[Language::EN] = $config;

        foreach ($configList as $key => $config) {

            // получаем объекты phpMorphy для разных словарей
            $this->morphy[$key] = new \phpMorphy(
                $config->directory,
                $config->language,
                $config->options
            );
        }
    }

    /**
     * @param $text
     * @return mixed
     */
    protected static function languageIdentify($text)
    {
        $result = Language::UNKNOWN;

        if (preg_match('/[А-Яа-яЁё]/', $text)) {
            $result = Language::RU;
        } elseif (preg_match('/[A-Za-z]/', $text)) {
            $result = Language::EN;
        }

        return $result;
    }

    /**
     * @param $word
     * @return \phpMorphy
     */
    protected function getMorphyObjectBySearchingToken($word)
    {
        $lang = self::languageIdentify($word);

        switch ($lang) {
            case Language::UNKNOWN:
                $morphy = $this->morphy[Language::RU];
                break;
            default:
                $morphy = $this->morphy[$lang];
        }

        return $morphy;
    }

    /**
     * @param string $toSearch
     * @return string[]
     */
    protected function getPseudoRoot($toSearch)
    {
        $morphy = $this->getMorphyObjectBySearchingToken($toSearch);
        return $morphy->getPseudoRoot($toSearch);
    }

    /**
     * @param string $toSearch
     * @param string $defaultEncoding
     * @return string
     */
    protected function getDictionaryEncoding($toSearch, $defaultEncoding = 'windows-1251')
    {
        $morphy = $this->getMorphyObjectBySearchingToken($toSearch);
        $resultEncoding = $morphy->getEncoding();

        $encodingsList = mb_list_encodings();
        if (!in_array($resultEncoding, $encodingsList)) {
            $resultEncoding = $defaultEncoding;
        }

        return $resultEncoding;
    }

    public function normalize(Token $srcToken)
    {
        $pseudoRootList = array();

        $toSearch = mb_strtoupper($srcToken->getTermText(), 'utf-8');

        $encoding = $this->getDictionaryEncoding($toSearch);
        /**
         * Если лексема короче MIN_TOKEN_LENGTH символов, то не используем её
         */
        if (mb_strlen($toSearch, 'utf-8') < self::MIN_TOKEN_LENGTH) {
            return null;
        }

        $toSearch = iconv('utf-8', "{$encoding}//IGNORE", $toSearch);

        if (mb_strlen($toSearch, $encoding) < self::MIN_TOKEN_LENGTH) {
            return null;
        }

        /**
         * хардкорно извлекаем 'псевдокорень' слова
         */
        $pseudoRootResult[] = $toSearch;
        do {
            $temp = $pseudoRootResult[0];
            $pseudoRootResult = $this->getPseudoRoot($temp);

            /**
             * если возвращается несколько - выбрать самым короткий `псевдокорень`
             */
            if (is_array($pseudoRootResult)) {
                usort(
                    $pseudoRootResult,
                    function ($a, $b) use ($encoding) {
                        $len1 = mb_strlen($a, $encoding);
                        $len2 = mb_strlen($b, $encoding);

                        return $len1 > $len2;
                    }
                );
            }

            $flag = $pseudoRootResult !== false && $pseudoRootResult[0] != $temp;

            if ($flag) {
                array_unshift($pseudoRootList, $pseudoRootResult[0]);
            }
        } while ($flag);


        if (count($pseudoRootList) == 0 && $pseudoRootResult === false) {
            /**
             * В случае если 'псевдокорень' получить не удалось, берем исходное слово целиком
             */
            $newTokenString = $toSearch;
        } else {
            /**
             * Из полученного списка 'псевдокорней' выберем первый,
             * длина которого не менее MIN_TOKEN_LENGTH
             */
            $newTokenString = null;

            foreach ($pseudoRootList as $pseudoRoot) {
                if (mb_strlen($pseudoRoot, $encoding) < self::MIN_TOKEN_LENGTH) {
                    continue;
                } else {
                    $newTokenString = $pseudoRoot;
                    break;
                }
            }

            /**
             * Если 'псевдокорень' не удалось получить даже сейчас, берем исходное слово целиком
             */
            if (is_null($newTokenString)) {
                $newTokenString = $toSearch;
            }
        }

        $newTokenString = iconv($encoding, 'utf-8//IGNORE', $newTokenString);

        $newToken = new Token(
            $newTokenString,
            $srcToken->getStartOffset(),
            $srcToken->getEndOffset()
        );

        $newToken->setPositionIncrement($srcToken->getPositionIncrement());

        return $newToken;
    }
}
