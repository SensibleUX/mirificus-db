<?php

/**
 * @package Mirificus
 */
namespace Mirificus;

/**
 * Every database adapter must implement the following 5 classes (all which are abstract):
 * * Database
 * * DatabaseField
 * * DatabaseResult
 * * DatabaseRow
 * * DatabaseException
 *
 * This Database library also has the following classes already defined, and 
 * Database adapters are assumed to use them internally:
 * * DatabaseIndex
 * * DatabaseForeignKey
 * * DatabaseFieldType (which is an abstract class that solely contains constants)
 *
 * @property-read string $EscapeIdentifierBegin
 * @property-read string $EscapeIdentifierEnd
 * @property-read boolean $EnableProfiling
 * @property-read int $AffectedRows
 * @property-read string $Profile
 * @property-read int $DatabaseIndex
 * @property-read int $Adapter
 * @property-read string $Server
 * @property-read string $Port
 * @property-read string $Database
 * @property-read string $Service
 * @property-read string $Protocol
 * @property-read string $Host
 * @property-read string $Username
 * @property-read string $Password
 * @property-read boolean $Caching if true objects loaded from this database will be kept in cache (assuming a cache provider is also configured)
 * @property-read string $DateFormat
 * @property-read boolean $OnlyFullGroupBy database adapter sub-classes can override and set this property to true
 *      to prevent the behavior of automatically adding all the columns to the select clause when the query has
 *      an aggregation clause.
 * @package Mirificus\Database
 */
abstract class Database
{
    // Must be updated for all Adapters

    const Adapter = 'Generic Database Adapter (Abstract)';

    // Protected Member Variables for ALL Database Adapters
    protected $intDatabaseIndex;
    protected $blnEnableProfiling;
    protected $strProfileArray;
    protected $objConfigArray;
    protected $blnConnectedFlag = false;
    protected $strEscapeIdentifierBegin = '"';
    protected $strEscapeIdentifierEnd = '"';
    protected $blnOnlyFullGroupBy = false; // should be set in sub-classes as appropriate

    // Abstract Methods that ALL Database Adapters MUST implement

    abstract public function Connect();

    // these are protected - externally, the "Query/NonQuery" wrappers are meant to be called
    abstract protected function ExecuteQuery($strQuery);

    abstract protected function ExecuteNonQuery($strNonQuery);

    abstract public function GetTables();

    abstract public function InsertId($strTableName = null, $strColumnName = null);

    abstract public function GetFieldsForTable($strTableName);

    abstract public function GetIndexesForTable($strTableName);

    abstract public function GetForeignKeysForTable($strTableName);

    abstract public function TransactionBegin();

    abstract public function TransactionCommit();

    abstract public function TransactionRollBack();

    abstract public function SqlLimitVariablePrefix($strLimitInfo);

    abstract public function SqlLimitVariableSuffix($strLimitInfo);

    abstract public function SqlSortByVariable($strSortByInfo);

    abstract public function Close();

    public function EscapeIdentifier($strIdentifier)
    {
        return $this->strEscapeIdentifierBegin . $strIdentifier . $this->strEscapeIdentifierEnd;
    }

    public function EscapeIdentifiers($mixIdentifiers)
    {
        if (is_array($mixIdentifiers)) {
            return array_map(array($this, 'EscapeIdentifier'), $mixIdentifiers);
        } else {
            return $this->EscapeIdentifier($mixIdentifiers);
        }
    }

    public function EscapeValues($mixValues)
    {
        if (is_array($mixValues)) {
            return array_map(array($this, 'SqlVariable'), $mixValues);
        } else {
            return $this->SqlVariable($mixValues);
        }
    }

    public function EscapeIdentifiersAndValues($mixColumnsAndValuesArray)
    {
        $result = array();
        foreach ($mixColumnsAndValuesArray as $strColumn => $mixValue) {
            $result[$this->EscapeIdentifier($strColumn)] = $this->SqlVariable($mixValue);
        }
        return $result;
    }

    public function InsertOrUpdate($strTable, $mixColumnsAndValuesArray, $strPKNames = null)
    {
        $strEscapedArray = $this->EscapeIdentifiersAndValues($mixColumnsAndValuesArray);
        $strColumns = array_keys($strEscapedArray);
        $strUpdateStatement = '';
        foreach ($strEscapedArray as $strColumn => $strValue) {
            if ($strUpdateStatement) {
                $strUpdateStatement .= ', ';
            }
            $strUpdateStatement .= $strColumn . ' = ' . $strValue;
        }
        if (is_null($strPKNames)) {
            $strMatchCondition = 'target_.' . $strColumns[0] . ' = source_.' . $strColumns[0];
        } elseif (is_array($strPKNames)) {
            $strMatchCondition = '';
            foreach ($strPKNames as $strPKName) {
                if ($strMatchCondition) {
                    $strMatchCondition .= ' AND ';
                }
                $strMatchCondition .= 'target_.' . $this->EscapeIdentifier($strPKName) . ' = source_.' . $this->EscapeIdentifier($strPKName);
            }
        } else {
            $strMatchCondition = 'target_.' . $this->EscapeIdentifier($strPKNames) . ' = source_.' . $this->EscapeIdentifier($strPKNames);
        }
        $strTable = $this->EscapeIdentifierBegin . $strTable . $this->EscapeIdentifierEnd;
        $strSql = sprintf('MERGE INTO %s AS target_ USING %s AS source_ ON %s WHEN MATCHED THEN UPDATE SET %s WHEN NOT MATCHED THEN INSERT (%s) VALUES (%s)', $strTable, $strTable, $strMatchCondition, $strUpdateStatement, implode(', ', $strColumns), implode(', ', array_values($strEscapedArray))
        );
        $this->ExecuteNonQuery($strSql);
    }

    /**
     * @param string $strQuery The query string
     * @return DatabaseResult
     */
    public final function Query($strQuery)
    {
        $timerName = null;
        if (!$this->blnConnectedFlag) {
            $this->Connect();
        }

        if ($this->blnEnableProfiling) {
            $timerName = 'queryExec' . mt_rand();
            Timer::Start($timerName);
        }

        $result = $this->ExecuteQuery($strQuery);

        if ($this->blnEnableProfiling) {
            $dblQueryTime = QTimer::Stop($timerName);
            Timer::Reset($timerName);

            // Log Query (for Profiling, if applicable)
            $this->LogQuery($strQuery, $dblQueryTime);
        }

        return $result;
    }

    public final function NonQuery($strNonQuery)
    {
        if (!$this->blnConnectedFlag) {
            $this->Connect();
        }
        $timerName = '';
        if ($this->blnEnableProfiling) {
            $timerName = 'queryExec' . mt_rand();
            Timer::Start($timerName);
        }

        $result = $this->ExecuteNonQuery($strNonQuery);

        if ($this->blnEnableProfiling) {
            $dblQueryTime = Timer::Stop($timerName);
            Timer::Reset($timerName);

            // Log Query (for Profiling, if applicable)
            $this->LogQuery($strNonQuery, $dblQueryTime);
        }

        return $result;
    }

    public function __get($strName)
    {
        switch ($strName) {
            case 'EscapeIdentifierBegin':
                return $this->strEscapeIdentifierBegin;
            case 'EscapeIdentifierEnd':
                return $this->strEscapeIdentifierEnd;
            case 'EnableProfiling':
                return $this->blnEnableProfiling;
            case 'AffectedRows':
                return -1;
            case 'Profile':
                return $this->strProfileArray;
            case 'DatabaseIndex':
                return $this->intDatabaseIndex;
            case 'Adapter':
                $strConstantName = get_class($this) . '::Adapter';
                return constant($strConstantName) . ' (' . $this->objConfigArray['adapter'] . ')';
            case 'Server':
            case 'Port':
            case 'Database':
            // Informix naming
            case 'Service':
            case 'Protocol':
            case 'Host':

            case 'Username':
            case 'Password':
                return $this->objConfigArray[strtolower($strName)];
            case 'Caching':
                return $this->objConfigArray['caching'];
            case 'DateFormat':
                return (is_null($this->objConfigArray[strtolower($strName)])) ? (QDateTime::FormatIso) : ($this->objConfigArray[strtolower($strName)]);
            case 'OnlyFullGroupBy':
                return $this->blnOnlyFullGroupBy;

            default:
                try {
                    return parent::__get($strName);
                } catch (CallerException $objExc) {
                    $objExc->IncrementOffset();
                    throw $objExc;
                }
        }
    }

    /**
     * Constructs a Database Adapter based on the database index and the configuration array of properties for this particular adapter
     * Sets up the base-level configuration properties for this database,
     * namely DB Profiling and Database Index
     *
     * @param integer $intDatabaseIndex
     * @param string[] $objConfigArray configuration array as passed in to the constructor by QApplicationBase::InitializeDatabaseConnections();
     * @return void
     */
    public function __construct($intDatabaseIndex, $objConfigArray)
    {
        // Setup DatabaseIndex
        $this->intDatabaseIndex = $intDatabaseIndex;

        // Save the ConfigArray
        $this->objConfigArray = $objConfigArray;

        // Setup Profiling Array (if applicable)
        $this->blnEnableProfiling = Type::Cast($objConfigArray['profiling'], Type::Boolean);
        if ($this->blnEnableProfiling) {
            $this->strProfileArray = array();
        }
    }

    /**
     * Allows for the enabling of DB profiling while in middle of the script
     *
     * @return void
     */
    public function EnableProfiling()
    {
        // Only perform profiling initialization if profiling is not yet enabled
        if (!$this->blnEnableProfiling) {
            $this->blnEnableProfiling = true;
            $this->strProfileArray = array();
        }
    }

    /**
     * If EnableProfiling is on, then log the query to the profile array
     *
     * @param string $strQuery
     * @param double $dblQueryTime query execution time in milliseconds
     * @return void
     */
    private function LogQuery($strQuery, $dblQueryTime)
    {
        if ($this->blnEnableProfiling) {
            // Dereference-ize Backtrace Information
            $objDebugBacktrace = debug_backtrace();

            // get rid of unnecessary backtrace info in case of:
            // query
            if ((count($objDebugBacktrace) > 3) &&
                    (array_key_exists('function', $objDebugBacktrace[2])) &&
                    (($objDebugBacktrace[2]['function'] == 'QueryArray') ||
                    ($objDebugBacktrace[2]['function'] == 'QuerySingle') ||
                    ($objDebugBacktrace[2]['function'] == 'QueryCount'))) {
                $objBacktrace = $objDebugBacktrace[3];
            } else {
                if (isset($objDebugBacktrace[2])) {
                    // non query
                    $objBacktrace = $objDebugBacktrace[2];
                } else {
                    // ad hoc query
                    $objBacktrace = $objDebugBacktrace[1];
                }
                // get rid of reference to current object in backtrace array
                if (isset($objBacktrace['object'])) {
                    $objBacktrace['object'] = null;
                }
                for ($intIndex = 0, $intMax = count($objBacktrace['args']); $intIndex < $intMax; $intIndex++) {
                    $obj = $objBacktrace['args'][$intIndex];
                    if (($obj instanceof QQClause) || ($obj instanceof QQCondition)) {
                        $obj = sprintf("[%s]", $obj->__toString());
                    } elseif (is_null($obj)) {
                        $obj = 'null';
                    } elseif (gettype($obj) == 'integer') {
                        
                    } elseif (gettype($obj) == 'object') {
                        $obj = 'Object';
                    } else {
                        $obj = sprintf("'%s'", $obj);
                    }
                    $objBacktrace['args'][$intIndex] = $obj;
                }
            }

            // Push it onto the profiling information array
            $arrProfile = array(
                'objBacktrace' => $objBacktrace,
                'strQuery' => $strQuery,
                'dblTimeInfo' => $dblQueryTime);

            array_push($this->strProfileArray, $arrProfile);
        }
    }

    /**
     * Properly escapes $mixData to be used as a SQL query parameter.
     * If IncludeEquality is set (usually not), then include an equality operator.
     * So for most data, it would just be "=".  But, for example,
     * if $mixData is NULL, then most RDBMS's require the use of "IS".
     *
     * @param mixed $mixData
     * @param boolean $blnIncludeEquality whether or not to include an equality operator
     * @param boolean $blnReverseEquality whether the included equality operator should be a "NOT EQUAL", e.g. "!="
     * @return string the properly formatted SQL variable
     */
    public function SqlVariable($mixData, $blnIncludeEquality = false, $blnReverseEquality = false)
    {
        // Are we SqlVariabling a BOOLEAN value?
        if (is_bool($mixData)) {
            // Yes
            if ($blnIncludeEquality) {
                // We must include the inequality

                if ($blnReverseEquality) {
                    // Do a "Reverse Equality"
                    // Check against NULL, True then False
                    if (is_null($mixData)) {
                        return 'IS NOT NULL';
                    } elseif ($mixData) {
                        return '= 0';
                    } else {
                        return '!= 0';
                    }
                } else {
                    // Check against NULL, True then False
                    if (is_null($mixData)) {
                        return 'IS NULL';
                    } elseif ($mixData) {
                        return '!= 0';
                    } else {
                        return '= 0';
                    }
                }
            } else {
                // Check against NULL, True then False
                if (is_null($mixData)) {
                    return 'NULL';
                } elseif ($mixData) {
                    return '1';
                } else {
                    return '0';
                }
            }
        }

        // Check for Equality Inclusion
        if ($blnIncludeEquality) {
            if ($blnReverseEquality) {
                if (is_null($mixData)) {
                    $strToReturn = 'IS NOT ';
                } else {
                    $strToReturn = '!= ';
                }
            } else {
                if (is_null($mixData)) {
                    $strToReturn = 'IS ';
                } else {
                    $strToReturn = '= ';
                }        
            }
        } else {
            $strToReturn = '';
        }
        // Check for NULL Value
        if (is_null($mixData)) {
            return $strToReturn . 'NULL';
        }
        // Check for NUMERIC Value
        if (is_integer($mixData) || is_float($mixData)) {
            return $strToReturn . sprintf('%s', $mixData);
        }
        // Check for DATE Value
        if ($mixData instanceof DateTime) {
            /** @var DateTime $mixData */
            if ($mixData->IsTimeNull()) {
                return $strToReturn . sprintf("'%s'", $mixData->Format('YYYY-MM-DD'));
            } else {
                return $strToReturn . sprintf("'%s'", $mixData->Format(DateTime::FormatIso));
            }
        }

        // Assume it's some kind of string value
        return $strToReturn . sprintf("'%s'", addslashes($mixData));
    }

    public function PrepareStatement($strQuery, $mixParameterArray)
    {
        foreach ($mixParameterArray as $strKey => $mixValue) {
            if (is_array($mixValue)) {
                $strParameters = array();
                foreach ($mixValue as $mixParameter) {
                    array_push($strParameters, $this->SqlVariable($mixParameter));
                }
                $strQuery = str_replace(chr(QQNamedValue::DelimiterCode) . '{' . $strKey . '}', implode(',', $strParameters) . ')', $strQuery);
            } else {
                $strQuery = str_replace(chr(QQNamedValue::DelimiterCode) . '{=' . $strKey . '=}', $this->SqlVariable($mixValue, true, false), $strQuery);
                $strQuery = str_replace(chr(QQNamedValue::DelimiterCode) . '{!' . $strKey . '!}', $this->SqlVariable($mixValue, true, true), $strQuery);
                $strQuery = str_replace(chr(QQNamedValue::DelimiterCode) . '{' . $strKey . '}', $this->SqlVariable($mixValue), $strQuery);
            }
        }

        return $strQuery;
    }

    /**
     * Displays the OutputProfiling results, plus a link which will popup the details of the profiling.
     *
     * @return void
     */
    public function OutputProfiling()
    {
        if ($this->blnEnableProfiling) {
            printf('<form method="post" id="frmDbProfile%s" action="%s/profile.php"><div>', $this->intDatabaseIndex, __VIRTUAL_DIRECTORY__ . __PHP_ASSETS__);
            printf('<input type="hidden" name="strProfileData" value="%s" />', base64_encode(serialize($this->strProfileArray)));
            printf('<input type="hidden" name="intDatabaseIndex" value="%s" />', $this->intDatabaseIndex);
            printf('<input type="hidden" name="strReferrer" value="%s" /></div></form>', QApplication::HtmlEntities(QApplication::$RequestUri));

            $intCount = round(count($this->strProfileArray));
            if ($intCount == 0) {
                printf('<b>PROFILING INFORMATION FOR DATABASE CONNECTION #%s</b>: No queries performed.  Please <a href="#" onclick="var frmDbProfile = document.getElementById(\'frmDbProfile%s\'); frmDbProfile.target = \'_blank\'; frmDbProfile.submit(); return false;">click here to view profiling detail</a><br />', $this->intDatabaseIndex, $this->intDatabaseIndex);
            } elseif ($intCount == 1) {
                printf('<b>PROFILING INFORMATION FOR DATABASE CONNECTION #%s</b>: 1 query performed.  Please <a href="#" onclick="var frmDbProfile = document.getElementById(\'frmDbProfile%s\'); frmDbProfile.target = \'_blank\'; frmDbProfile.submit(); return false;">click here to view profiling detail</a><br />', $this->intDatabaseIndex, $this->intDatabaseIndex);
            } else {
                printf('<b>PROFILING INFORMATION FOR DATABASE CONNECTION #%s</b>: %s queries performed.  Please <a href="#" onclick="var frmDbProfile = document.getElementById(\'frmDbProfile%s\'); frmDbProfile.target = \'_blank\'; frmDbProfile.submit(); return false;">click here to view profiling detail</a><br />', $this->intDatabaseIndex, $intCount, $this->intDatabaseIndex);
            }
        } else {
             echo '<form></form><b>Profiling was not enabled for this database connection (#' . $this->intDatabaseIndex . ').</b>  To enable, ensure that ENABLE_PROFILING is set to TRUE.';
        }
    }

    /**
     * Executes the explain statement for a given query and returns the output without any transformation.
     * If the database adapter does not support EXPLAIN statements, returns null.
     *
     * @param string $sql
     */
    public function ExplainStatement($sql)
    {
        return null;
    }

}
