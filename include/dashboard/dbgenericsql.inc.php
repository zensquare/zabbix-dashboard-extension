<?php

require_once dirname(__FILE__) . '/dbblock.inc.php';

class DBGenericSQL extends DBBlock {

    protected $emptyText = "";
    protected $columns = array();
    protected $headers = true;
    protected $rowClass = null;
    protected $evalRowClass = false;

    
    public function init(){
//        $this->sandbox = new Runkit_Sandbox();
        
        if(isset($this->config['emptytext'])){
            $this->emptyText = $this->config['emptytext'];
        } else {
            $this->emptyText = _("No records");
        }
        
        if(isset($this->config['columns']) && is_array($this->config['columns'])){
            foreach($this->config['columns'] as $encodedColumn){
                $encodedColumn = trim($encodedColumn);
                
                $column = array(
                    'eval'=>false,
                    'evalClass'=>false,
                    'class'=>null,
                    'width'=>null
                );
                
                if($encodedColumn[0] != "{"){
                    $column['name'] = $encodedColumn;
                    $column['value'] = $encodedColumn;
                } else {
                    $decoded = json_decode($encodedColumn,true);
                    $column = array_merge($column, $decoded);
                    
                    if(is_array($column['class'])) {
                        $column['evalClass'] = true;
                        $column['class'] = $this->cleanClasses($column['class']);
                    }
                }
                
                if(preg_match('/^\s*\w+\(.+\)\s*$/', $column['value'], $matches)){
                    $column['eval'] = true;
                }
                
                $this->columns[] = $column;
            }
        }
        
        $this->headers = !isset($this->config['headers'])||$this->config['headers']==1;
        
        if(isset($this->config['row_class'])){
            if($this->config['row_class'][0] != "{"){
                $this->rowClass = $this->config['row_class'];
            } else {
                $this->evalRowClass = true;
                $this->rowClass = $this->cleanClasses(json_decode($this->config['row_class']));
            }
        }
        
    }
    
    public function _getContent($refresh = false) {                
        
        if(!isset($this->config['sql'])){
            return "SQL not set";
        }
        
        $table = new CTableInfo($this->emptyText);
        
        
        $resultSet = DBselect($this->config['sql']);
        $result = DbFetchArray($resultSet);
        
        if(!empty($this->columns)) {
            $headers = array();
            foreach($this->columns as $column){
                $headers[] = !empty($column['name'])?$column['name']:$column['field'];
            }
        } else {
            if(isset($result[0])){
                
                foreach($result[0] as $key => $value){
                    $headers[] = $key;
                    $column = array(
                        'eval'=>false,
                        'evalClass'=>false,
                        'class'=>null,
                        'width'=>null,
                        'name'=>$key,
                        'value'=>$key
                    );
                    $this->columns[] = $column;
                }
            } else {
                $headers[] = "Empty Table";
            }
        }
            
        if($this->headers){
            $table->setHeader($headers);
        }
                
        $index = 0;
	foreach ($result as $row) {
            $row['index'] = $index++;
            
            $crow = new CRow(null, $this->getRowClass($row));
            
            foreach($this->columns as $column){
                $cell = new CCol(
                        $this->getValue($column, $row),
                        $this->getClass($column, $row)
                        );      
                $crow->addItem($cell);
            }
            
            $table->addRow($crow);
	}
           
        return $table;
    }
    
    public function evalColumn($column, $row){
        return "Not implemented";
    }
    
    public function getValue($column, $row){
        if($column['eval']){
            return $this->evalColumn($column, $row);
        }
        return $row[$column['value']];
    }
        
    public function cleanClasses($classes){
        $okExpressions = array();
        foreach($classes as $classname => $expression){
            if($this->checkEval($expression)){
                $expression = preg_replace(array(
                        "/([a-zA-Z_]\w+)/", //Replace variables with $row[$1]
                        "/([^=><])=([^=])/" //Replace single equals with double (no assignment you you!)
                    ), array(
                        '\\$row[\'\1\']',
                        '\1==\2'
                    ), $expression);
                $okExpressions[$classname] = 'return '.$expression.';';
            }
        }
        return $okExpressions;
    }
    
    public function checkEval($expression){
        return preg_match("/\w+\s*\(|\\$/", $expression)==0; //No functions or variables for you
    }
    
    public function evalClass($definition, $row){
        $classes = "";
        foreach($definition as $classname => $expression){
            if(eval($expression)){
               $classes .= " " . $classname; 
            }
        }
        return empty($classes)?null:$classes;
    }
    
    public function getClass($column, $row){
        if($column['evalClass']){
            return $this->evalClass($column['class'], $row);
        }
        return $column['class'];
    }
    
    public function getRowClass($row){
        if($this->evalRowClass){
            return $this->evalClass($this->rowClass, $row);
        }
        return $this->rowClass;
    }
}