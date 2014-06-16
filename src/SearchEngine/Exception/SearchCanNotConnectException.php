<?php
namespace SearchEngine\Exception;

/**
 * Class SearchCanNotConnectException
 *
 * Класс исключения при ошибке доступа к поисковому индексу
 *
 * @package SearchEngine\Exception
 */
class SearchCanNotConnectException extends \Exception
{
    /**
     * Constructs an SearchCanNotConnectException for ZendLucene at location $location
     *
     * @param string $location
     */
    public function __construct($location)
    {
        $message = "Could not connect to ZendLucene index at '$location'.";
        parent::__construct($message);
    }
}
