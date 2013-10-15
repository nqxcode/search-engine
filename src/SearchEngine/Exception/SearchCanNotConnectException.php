<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Andrey
 * Date: 11.10.13
 * Time: 17:06
 * SearchCanNotConnectException
 */

namespace SearchEngine\Exception;

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