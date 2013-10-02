<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 * @package Mirificus\DatabaseForeignKey
 */
class DatabaseForeignKey
{
    /** @var string $strKeyName Foreign key name. */
    protected $strKeyName;

    /** @var string[] Array of column names. */
    protected $strColumnNameArray;

    /** @var string The name of the reference table. */
    protected $strReferenceTableName;

    /** @var string[] Array of reference column names. */
    protected $strReferenceColumnNameArray;

    /**
     * Constructor
     * @param string $strKeyName
     * @param string[] $strColumnNameArray
     * @param string $strReferenceTableName
     * @param string[] $strReferenceColumnNameArray
     */
    public function __construct($strKeyName, $strColumnNameArray, $strReferenceTableName, $strReferenceColumnNameArray)
    {
        $this->strKeyName = $strKeyName;
        $this->strColumnNameArray = $strColumnNameArray;
        $this->strReferenceTableName = $strReferenceTableName;
        $this->strReferenceColumnNameArray = $strReferenceColumnNameArray;
    }

    /**
     * Magic method: get
     * @param string $strName Property name to get.
     * @return mixed The requested property.
     * @throws \Mirificus\CallerException
     */
    public function __get($strName)
    {
        switch ($strName) {
            case "KeyName":
                return $this->strKeyName;
            case "ColumnNameArray":
                return $this->strColumnNameArray;
            case "ReferenceTableName":
                return $this->strReferenceTableName;
            case "ReferenceColumnNameArray":
                return $this->strReferenceColumnNameArray;
            default:
                try {
                    return parent::__get($strName);
                } catch (CallerException $objExc) {
                    $objExc->IncrementOffset();
                    throw $objExc;
                }
        }
    }

}
