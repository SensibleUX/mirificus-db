<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 *
 * @package DatabaseAdapters
 */
class DatabaseIndex
{
    /** @var string $strKeyName */
    protected $strKeyName;
    
    /** @var bool $blnPrimaryKey Is this a primary key? */
    protected $blnPrimaryKey;
    
    /** @var bool $blnUnique Is this a unique field? */
    protected $blnUnique;
    
    /** @var string[] $strColumnNameArray Array of column names. */
    protected $strColumnNameArray;

    /**
     * Constructor
     * @param string $strKeyName
     * @param bool $blnPrimaryKey
     * @param bool $blnUnique
     * @param string[] $strColumnNameArray
     */
    public function __construct($strKeyName, $blnPrimaryKey, $blnUnique, $strColumnNameArray)
    {
        $this->strKeyName = $strKeyName;
        $this->blnPrimaryKey = $blnPrimaryKey;
        $this->blnUnique = $blnUnique;
        $this->strColumnNameArray = $strColumnNameArray;
    }

    /**
     * Magic method: get
     * @param string $strName The name of the property to get.
     * @return mixed The requested property.
     * @throws \Mirificus\CallerException
     */
    public function __get($strName)
    {
        switch ($strName) {
            case "KeyName":
                return $this->strKeyName;
            case "PrimaryKey":
                return $this->blnPrimaryKey;
            case "Unique":
                return $this->blnUnique;
            case "ColumnNameArray":
                return $this->strColumnNameArray;
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
