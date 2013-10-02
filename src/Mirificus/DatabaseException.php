<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 *
 * @package Mirificus\DatabaseException
 */
abstract class DatabaseException extends CallerException
{
    /** @var int $intErrorNumber The error code. */
    protected $intErrorNumber;
    
    /** @var string $strQuery The query string. */
    protected $strQuery;

    public function __get($strName)
    {
        switch ($strName) {
            case "ErrorNumber":
                return $this->intErrorNumber;
            case "Query";
                return $this->strQuery;
            default:
                return parent::__get($strName);
        }
    }

}
