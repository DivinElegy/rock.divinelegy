<?php

namespace DataAccess\Queries;

use DataAccess\Queries\IQueryBuilder;

class QueryBuilder implements IQueryBuilder
{
    private $_queryString = 'SELECT * FROM %s';
    
    protected $_whereClauses = array();
    protected $_limitClause;
    protected $_joinClauses = array();
    
    public function buildQuery()
    {        
        $this->applyJoinClauses()
             ->applyWhereClauses()
             ->applyLimitClause();

        return $this->_queryString;
    }
    
    public function setBaseQuery($baseQuery)
    {
        $this->_queryString = $baseQuery;
    }
    
    public function where($columnName, $operator, $value)
    {
        $this->_whereClauses[$columnName] = array('operator' => $operator, 'value' => $value);
        return $this;
    }
    
    public function limit($start, $end = null)
    {
        if($end) 
        {
            $this->_limitClause = sprintf(' LIMIT %u,%u', $start, $end);
            return $this;
        }
        
        $this->_limitClause = sprintf(' LIMIT %u', $start);
        
        return $this;
    }
    
    public function join($type, $tableA, $columnA, $tableB, $columnB)
    {
        $this->_joinClauses[] = sprintf(' %s JOIN %s ON %s.%s = %s.%s', $type, $tableB, $tableA, $columnA, $tableB, $columnB);
        return $this;
    }
    
    public function null($columnName)
    {
        return $this->where($columnName, 'is', null);
    }
    
    private function applyJoinClauses()
    {
        foreach($this->_joinClauses as $joinClause)
        {
            $this->_queryString .= $joinClause;
        }
        
        return $this;
    }
    
    private function applyWhereClauses()
    {
        $this->_queryString .= ' WHERE ';
        
        foreach($this->_whereClauses as $columnName => $columnValue)
        {
            switch(gettype($columnValue['value']))
            {
                case 'integer':
                    $this->_queryString .= sprintf("%s%s%u", $columnName, $columnValue['operator'], $columnValue['value']) . ' AND ';
                    break;
                case 'string':
                    $this->_queryString .= sprintf("%s %s '%s'", $columnName, $columnValue['operator'], $columnValue['value']) . ' AND ';
                    break;
                case 'NULL':
                    $this->_queryString .= sprintf("%s is null", $columnName) . ' AND ';
                    break;
            }
            
        }
        
        $this->_queryString = rtrim($this->_queryString, ' AND ');
        return $this;
    }
    
    private function applyLimitClause()
    {
        $this->_queryString .= $this->_limitClause;
        return $this;
    }
    
}