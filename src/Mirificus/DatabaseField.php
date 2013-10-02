<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 * DatabaseField
 */
abstract class DatabaseField
{

    /** @var string $strName The name of the field. */
    protected $strName;

    /** @var string $strOriginalName The original name of the field. */
    protected $strOriginalName;

    /** @var string $strTable The table name. */
    protected $strTable;

    /** @var string $strOriginalTable The original table name. */
    protected $strOriginalTable;

    /** @var string $strDefault The default value for the field. */
    protected $strDefault;

    /** @var int $intMaxLength The maximum length of the field. */
    protected $intMaxLength;

    /** @var string $strComment The comment for the field. */
    protected $strComment;

    /** @var bool $blnIdentity Is this is an identity column? (auto-increment) */
    protected $blnIdentity;

    /** @var bool $blnNotNull Is not null set? */
    protected $blnNotNull;

    /** @var bool $blnPrimaryKey Is this a primary key? */
    protected $blnPrimaryKey;

    /** @var bool $blnUnique Is this value unique? */
    protected $blnUnique;

    /** @var bool $blnTimestamp Is this a timestamp field? */
    protected $blnTimestamp;

    /** @var string $strType The type of the field. */
    protected $strType;

    public function __get($strName)
    {
        switch ($strName) {
            case "Name":
                return $this->strName;
            case "OriginalName":
                return $this->strOriginalName;
            case "Table":
                return $this->strTable;
            case "OriginalTable":
                return $this->strOriginalTable;
            case "Default":
                return $this->strDefault;
            case "MaxLength":
                return $this->intMaxLength;
            case "Identity":
                return $this->blnIdentity;
            case "NotNull":
                return $this->blnNotNull;
            case "PrimaryKey":
                return $this->blnPrimaryKey;
            case "Unique":
                return $this->blnUnique;
            case "Timestamp":
                return $this->blnTimestamp;
            case "Type":
                return $this->strType;
            case "Comment":
                return $this->strComment;
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
