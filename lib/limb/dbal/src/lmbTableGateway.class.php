<?php
/**
 * Limb Web Application Framework
 *
 * @link http://limb-project.com
 *
 * @copyright  Copyright &copy; 2004-2007 BIT
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 * @version    $Id: lmbTableGateway.class.php 4994 2007-02-08 15:36:08Z pachanga $
 * @package    dbal
 */
lmb_require('limb/dbal/src/query/lmbInsertQuery.class.php');
lmb_require('limb/dbal/src/query/lmbSelectQuery.class.php');
lmb_require('limb/dbal/src/criteria/lmbSQLFieldCriteria.class.php');
lmb_require('limb/dbal/src/query/lmbUpdateQuery.class.php');
lmb_require('limb/dbal/src/query/lmbDeleteQuery.class.php');
lmb_require('limb/dbal/src/lmbCachedDatabaseInfo.class.php');
lmb_require('limb/dbal/src/drivers/lmbDbTypeInfo.class.php');

class lmbTableGateway
{
  protected $_db_table_name;
  protected $_primary_key_name;
  protected $_table_info;
  protected $_constraints = array();
  protected $_conn;
  protected $_stmt;

  function __construct($table_name = null, $conn = null)
  {
    if(is_null($conn))
      $this->_conn = lmbToolkit :: instance()->getDefaultDbConnection();
    else
      $this->_conn = $conn;

    if($table_name)
      $this->_db_table_name = $table_name;
    elseif(!$this->_db_table_name)
      $this->_db_table_name = $this->_guessDbTableName();

    $this->_table_info = $this->_loadTableInfo();
    $this->_constraints = $this->_defineConstraints();
    $this->_primary_key_name = $this->_definePrimaryKeyName();
  }

  protected function _guessDbTableName()
  {
    return str_replace('_db_table', '', to_under_scores(get_class($this)));
  }

  protected function _definePrimaryKeyName()
  {
    return 'id';
  }

  protected function _loadTableInfo()
  {
    $db_info = new lmbCachedDatabaseInfo($this->_conn);
    return $db_info->getTable($this->_db_table_name);
  }

  protected function _defineConstraints()
  {
    return array();
  }

  function getTableInfo()
  {
    return $this->_table_info;
  }

  function getColumnInfo($name)
  {
    if($this->hasColumn($name))
      return $this->_table_info->getColumn($name);
  }

  function hasColumn($name)
  {
    return $this->_table_info->hasColumn($name);
  }

  function getColumnNames()
  {
    return $this->_table_info->getColumnList();
  }

  function getConstraints()
  {
    return $this->_constraints;
  }

  function getColumnType($column_name)
  {
    if(!$this->hasColumn($column_name))
      return false;

    return $this->_table_info->getColumn($column_name)->getType();
  }

  function getPrimaryKeyName()
  {
    return $this->_primary_key_name;
  }

  function isAutoIncrement($field)
  {
    return $this->_table_info->getColumn($field)->isAutoIncrement();
  }

  function getStatement()
  {
    return $this->_stmt;
  }

  function getAffectedRowCount()
  {
    if($this->_stmt)
      return $this->_stmt->getAffectedRowCount();
    else
      return 0;
  }

  function insert($row)
  {
    $filtered_row = $this->_filterRow($row);
    if(!count($filtered_row))
      throw new lmbException('All fields filtered!! Insert statement must contain atleast one field!');

    $query = new lmbInsertQuery($this->_db_table_name, $this->_conn);
    $values = array();
    $update_sequence = false;
    foreach($filtered_row as $key => $value)
    {
      if(is_null($value) && $this->isAutoIncrement($key))
        continue;

      $query->addField($key);
      $values[$key] = $value;
    }

    $this->_stmt = $query->getStatement($this->_conn);

    $this->_bindValuesToStatement($this->_stmt, $values);

    return (int)$this->_stmt->insertId($this->_primary_key_name);
  }

  function update($row, $criteria = null)
  {
    $row = $this->_filterRow($row);
    $query = new lmbUpdateQuery($this->_db_table_name, $this->_conn);

    if($criteria)
      $query->addCriteria($criteria);

    foreach(array_keys($row) as $key)
      $query->addField($key);

    $this->_stmt = $query->getStatement();

    $this->_bindValuesToStatement($this->_stmt, $row);

    return $this->_stmt->execute();
  }

  protected function _bindValuesToStatement($stmt, $values)
  {
    $typeinfo = new lmbDbTypeInfo();
    $accessors = $typeinfo->getColumnTypeAccessors();

    foreach($values as $key => $value)
    {
      $accessor = $accessors[$this->getColumnInfo($key)->getType()];
      $stmt->$accessor($key, $value);
    }
  }

  function updateById($id, $data)
  {
    return $this->update($data, new lmbSQLFieldCriteria($this->_primary_key_name, $id));
  }

  function selectRecordById($id, $fields = array())
  {
    if($id == null)
      return null;

    $query = $this->getSelectQuery($fields);

    $query->addCriteria(new lmbSQLFieldCriteria($this->_primary_key_name, $id));
    $record_set = $query->getRecordSet();
    $record_set->rewind();

    if(!$record_set->valid())
      return null;
    else
      return $record_set->current();
  }

  function select($criteria = null, $sort_params = array(), $fields = array())
  {
    $query = $this->getSelectQuery($fields);

    if($criteria)
      $query->addCriteria($criteria);

    $rs = $query->getRecordSet();

    if(count($sort_params))
      $rs->sort($sort_params);

    return $rs;
  }

  function selectFirstRecord($criteria = null, $sort_params = array(), $fields = array())
  {
    $rs = $this->select($criteria, $sort_params, $fields);

    if($sort_params)
      $rs->sort($sort_params);

    $rs->rewind();
    if($rs->valid())
      return $rs->current();
  }

  function getSelectQuery($fields = array())
  {
    $query = new lmbSelectQuery(null, $this->_conn);
    $query->addTable($this->_db_table_name);

    if(!$fields)
      $fields = $this->getColumnsForSelect();

    foreach($fields as $field)
      $query->addField($field);

    return $query;
  }

  function delete($criteria = null)
  {
    $query = new lmbDeleteQuery($this->_db_table_name, $this->_conn);

    if($criteria)
      $query->addCriteria($criteria);

    $this->_stmt = $query->getStatement();
    $this->_stmt->execute();
  }

  function deleteById($id)
  {
    return $this->delete(new lmbSQLFieldCriteria($this->_primary_key_name, $id));
  }

  function getTableName()
  {
    return $this->_db_table_name;
  }

  protected function _mapTableNameToClass($table_name)
  {
    return toStudlyCaps($table_name);
  }

  function getColumnsForSelect($table_name = '', $exclude_columns = array(), $prefix = '')
  {
    if(!$table_name)
      $table_name = $this->getTableName();

    $columns = $this->getColumnNames();
    $fields = array();
    foreach($columns as $name)
    {
      if(!in_array($name, $exclude_columns))
        $fields[$table_name . '.' . $name] = $prefix . $name;
    }

    return $fields;
  }

  protected function _filterRow($row)
  {
    if(!is_array($row))
      return array();

    $filtered = array();
    foreach($row as $key => $value)
    {
      if(is_integer($key))
        $filtered[$key] = $value;
      elseif($this->hasColumn($key))
        $filtered[$key] = $value;
    }
    return $filtered;
  }
}

?>