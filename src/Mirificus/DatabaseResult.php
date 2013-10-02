<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 * @property QueryBuilder Allows attaching a QueryBuilder object to use the result object as cursor resource for cursor queries.
 */
abstract class DatabaseResult
{

    /** @var $objQueryBuilder Allows attaching a QueryBuilder object to use the result object as cursor resource for cursor queries. */
    protected $objQueryBuilder;

    /** */
    abstract public function FetchArray();

    /** */
    abstract public function FetchRow();

    /** */
    abstract public function FetchField();

    /** */
    abstract public function FetchFields();

    /** */
    abstract public function CountRows();

    /** */
    abstract public function CountFields();

    /** */
    abstract public function GetNextRow();

    /** */
    abstract public function GetRows();

    /** */
    abstract public function Close();

    public function __get($strName)
    {
        switch ($strName) {
            case 'QueryBuilder':
                return $this->objQueryBuilder;
            default:
                try {
                    return parent::__get($strName);
                } catch (CallerException $objExc) {
                    $objExc->IncrementOffset();
                    throw $objExc;
                }
        }
    }

    public function __set($strName, $mixValue)
    {
        switch ($strName) {
            case 'QueryBuilder':
                try {
                    return ($this->objQueryBuilder = Type::Cast($mixValue, 'QueryBuilder'));
                } catch (InvalidCastException $objExc) {
                    $objExc->IncrementOffset();
                    throw $objExc;
                }
            default:
                try {
                    return parent::__set($strName, $mixValue);
                } catch (CallerException $objExc) {
                    $objExc->IncrementOffset();
                    throw $objExc;
                }
        }
    }

}
