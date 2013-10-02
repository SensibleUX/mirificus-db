<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 * Constants for DB types.
 * @package Mirificus\DatabaseFieldType
 */
abstract class DatabaseFieldType
{
    const Blob = "Blob";
    const VarChar = "VarChar";
    const Char = "Char";
    const Integer = "Integer";
    const DateTime = "DateTime";
    const Date = "Date";
    const Time = "Time";
    const Float = "Float";
    const Bit = "Bit";
}
