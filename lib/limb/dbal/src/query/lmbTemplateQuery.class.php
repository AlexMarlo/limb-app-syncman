<?php
/**
 * Limb Web Application Framework
 *
 * @link http://limb-project.com
 *
 * @copyright  Copyright &copy; 2004-2007 BIT
 * @license    LGPL http://www.gnu.org/copyleft/lesser.html
 * @version    $Id: lmbTemplateQuery.class.php 4994 2007-02-08 15:36:08Z pachanga $
 * @package    dbal
 */

class lmbTemplateQuery
{
  protected $_template_sql;
  protected $_no_hints_sql;
  protected $_conn;

  function __construct($template_sql, $conn)
  {
    $this->_template_sql = $template_sql;
    $this->_conn = $conn;
  }

  protected function _declareHints()
  {
    return array();
  }

  function _wrapHint($hint)
  {
    return "%$hint%";
  }

  function _getWrappedHints()
  {
    return array_map(array($this, '_wrapHint'), $this->_declareHints());
  }

  function _fillHints()
  {
    $hints = $this->_declareHints();
    $result = array();
    foreach($hints as $hint)
    {
      $method = '_get' . ucfirst(toStudlyCaps($hint)) . 'Hint';
      $result[$this->_wrapHint($hint)] = $this->$method();
    }
    return $result;
  }

  function toString()
  {
    $hints = $this->_fillHints();
    $this->_validateSQLforTemplateHints($hints);

    return trim(strtr($this->_template_sql, $hints));
  }

  protected function _validateSQLforTemplateHints($hints)
  {
    foreach($hints as $hint => $value)
    {
      if(!trim($value)) continue;

      if(strpos($this->_template_sql, $hint) === false)
        throw new lmbException("Template hint '$hint' not for value '$value' found in '$this->_template_sql'");
    }
  }

  function getStatement()
  {
    return $this->_conn->newStatement($this->toString());
  }

  protected function _getNoHintsSQL()
  {
    if($this->_no_hints_sql)
      return $this->_no_hints_sql;

    $result = array();
    foreach($this->_getWrappedHints() as $hint)
      $result[$hint] = '';

    $this->_no_hints_sql = strtr($this->_template_sql, $result);
    return $this->_no_hints_sql;
  }
}
?>
