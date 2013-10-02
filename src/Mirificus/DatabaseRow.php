<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 *
 * @package Mirificus\DatabaseRow
 */
abstract class DatabaseRow
{
    /**
     * Get a single column.
     * @param string $strColumnName The name of the column to get.
     * @param string $strColumnType The type of the column to get.
     */
    abstract public function GetColumn($strColumnName, $strColumnType = null);

    /**
     * Does a column exist?
     * @param string $strColumnName The column name to check.
     */
    abstract public function ColumnExists($strColumnName);

    /**
     * Get the names of all columns.
     */
    abstract public function GetColumnNameArray();
}
