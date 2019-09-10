<?php
namespace PhpDatabaseModel;

use PhpDatabaseModel\Datasource;

class Model
{
    public $className, $validationErrors = [];
    protected $table, $primaryKey, $datasource, $children = [], $parents = [], $id = null, $data = null;
    
    public static $tableInfo = [];

    public function __construct($table = null, \PhpDatabaseModel\Datasource $datasource = null)
    {
        if (!$datasource)
        {
            $datasource = $this->initDataSource();
        }
        
        if (!$datasource)
        {
            throw new Exception("No Datasource config found");
        }
        
        $this->datasource = $datasource;
        
        if (!$table)
        {
            $this->className = get_class($this);
            $this->table = Inflector::tableize($this->className);
        }
        
        $this->primaryKey = "id";
    }
    
    public function initDataSource()
    {
        return null;
    }
    
    public function getDataSource()
    {
        return $this->datasource;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function select(\QueryBuilder\QuerySelect $qs, $callback)
    {
        if ($callback)
        {
            if (!$this->beforeSelect($qs))
            {
                return false;
            }
        }
        
        $fields = $qs->getFields();
        if ($fields && !isset($fields[$this->primaryKey]))
        {
            $qs->field($this->primaryKey);
        }
        
        $data = $this->getDataSource()->select($qs->get());
        
        $records = array();
        foreach($data as $arr)
        {
            $records[] = array($this->className => $arr);
        }
        
        if ($this->children)
        {
            $primary_id_list = Extract::extract($records, "{n}." . $this->className . "." . $this->primaryKey);
            $primary_id_list = array_keys(array_flip($primary_id_list));
            foreach($this->children as $alias => $join)
            {
                $wh = $join->qs->getWhere();
                $wh->add($join->key, $primary_id_list);

                $child_records = $join->model->select($join->qs, $callback);
                foreach($records as $k => $record)
                {
                    foreach($child_records as $ck => $child_record)
                    {
                        if ($record[$this->className][$this->primaryKey] == $child_record[$join->alias][$join->key])
                        {
                            $records[$k][$join->alias][] = $child_record;                            
                        }
                    }
                }
            }
        }
        if ($this->parents)
        {
            foreach($this->parents as $alias => $join)
            {
                $key_id_list = Extract::extract($records, "{n}." . $this->className . "." . $join->key);
                $key_id_list = array_keys(array_flip($key_id_list));
                $wh = $join->qs->getWhere();
                $wh->add($this->primaryKey, $key_id_list);

                $parent_records = $join->model->select($join->qs, $callback);
                foreach($records as $k => $record)
                {
                    foreach($parent_records as $ck => $parent_record)
                    {
                        if ($record[$this->className][$join->key] == $parent_record[$join->alias][$this->primaryKey])
                        {
                            $records[$k][$join->alias] = $parent_record[$join->alias];                            
                        }
                    }
                }
            }
        }
        
        return $this->afterSelect($records);
    }
    
    public function selectField($field)
    {
        $qs = new \QueryBuilder\QuerySelect();
        $qs->field($field);
        $wh = $qs->getWhere();
        $wh->add($this->primaryKey, $this->id);
        
        $data = $this->getDataSource()->select($qs->get());
        
        if ($data)
        {
            return $data[0][$field];
        }
        
        return false;
    }
    
    public function selectCount(\QueryBuilder\Where $wh)
    {
        $qs = new \QueryBuilder\QuerySelect();
        $qs->field("count(1)", "c");
        $qs->setWhere($wh);
        
        $data = $this->getDataSource()->select($qs->get());
        
        if ($data)
        {
            return $data[0]["c"];
        }
        
        return false;
    }
    
    public function beforeSelect(\QueryBuilder\QuerySelect $qs)
    {
        return true;
    }
    
    public function afterSelect(array $records)
    {
        return $records;
    }
    
    public function addChild(Join $join)
    {
        $this->children[$join->alias] = $join;
    }
    
    public function addParent(Join $join)
    {
        $this->parents[$join->alias] = $join;
    }
    
    public function save($data, $callback = true)
    {
        $this->data = $data;
        if ($callback)
        {
            if (!$this->beforeValidate())
            {
                return false;
            }
            
            if (!$this->beforeSave())
            {
                return false;
            }
        }
        
        $field_list = $this->getTableFieldList();
        
        $save_data = array();
        
        foreach($this->data as $field => $value)
        {
            if (isset($field_list[$field]))
            {
                switch($field_list[$field])
                {
                    case "tinyint":
                        $value = (int) $value;
                        break;
                    
                    case "date":
                        break;
                }
                
                $this->data[$field] = $value;
                $save_data[$field] = $value;
            }
        }
        
        $will_insert = false;
        if ($this->id)
        {
            $result = $this->_update($save_data);
        }
        else
        {
            $will_insert = true;
            $result = $this->_insert($save_data);
        }
        
        if (!$result)
        {
            return false;
        }
        
        if ($callback)
        {
            $this->afterSave($will_insert);
        }
        
        return TRUE;
    }
    
    public function updateAll($data, \QueryBuilder\Where $wh)
    {
        $db = $this->getDataSource();
        
        $list = array();
        
        foreach ($data as $field => $value)
        {
            $list[] = $db->getCleanName($field) . "='" . $value . "'";
        }
        
        $fields = implode(", ", $list);
        
        $q = "UPDATE " . $db->getCleanName($this->getTable()) . " SET $fields WHERE " . $wh->get();
        
        return $db->update($q);
    }
    
    public function delete()
    {
        $db = $this->getDataSource();
        
        $q = "DELETE FROM " . $db->getCleanName($this->getTable()) . " WHERE " . $this->primaryKey . "=" . $this->id;
        
        return $db->delete($q);
    }
    
    public function deleteAll(\QueryBuilder\Where $wh)
    {
        $db = $this->getDataSource();
        
        $q = "DELETE FROM " . $db->getCleanName($this->getTable()) . " WHERE " . $wh->get();
        
        return $db->delete($q);
    }
    
    private function _insert($data)
    {
        $db = $this->getDataSource();
        
        $field_list = $value_list = array();
        
        foreach ($data as $field => $value)
        {
            $field_list[] = $db->getCleanName($field);
            $value_list[] = "'" . $value . "'";
        }
        
        $fields = implode(", ", $field_list);
        $values = implode(", ", $value_list);
        
        $q = "INSERT INTO " . $db->getCleanName($this->getTable()) . "($fields)VALUES($values);";
        
        $result = $db->insert($q);
        
        $this->id = $db->getLastInsertId();
        
        return $result;
    }
    
    private function _update($data)
    {
        $db = $this->getDataSource();
        
        $list = array();
        
        foreach ($data as $field => $value)
        {
            $list[] = $db->getCleanName($field) . "='" . $value . "'";
        }
        
        $fields = implode(", ", $list);
        
        $q = "UPDATE " . $db->getCleanName($this->getTable()) . " SET $fields WHERE " . $this->primaryKey . "=" . $this->id;
        
        return $db->update($q);
    }
    
    protected function beforeValidate()
    {
        $validator = $this->getValidator();
        
        $field_rules = $this->getValidationRules();
        
        $this->validationErrors = [];
        
        foreach($field_rules as $field => $rules)
        {
            if ( isset($this->data[$field]) )
            {
                foreach($rules as $rule => $rule_arr)
                {
                    if (!isset($rule_arr["args"]))
                    {
                        $rule_arr["args"] = array();
                    }
                    
                    if (!isset($rule_arr["msg"]))
                    {
                        throw new Exception("$this->className : msg is missing from $rule in $field");
                    }
                    
                    $will_validate = true;
                    if ($rule == "isUnique")
                    {
                        $rule_arr["fieldName"] = $field;
                        $rule_arr["fieldValue"] = $this->data[$field];
                        
                        if (empty($rule_arr["fieldValue"]) && isset($rule_arr["allowEmpty"]) && $rule_arr["allowEmpty"])
                        {
                            $will_validate = false;
                        }
                        
                        if ($this->id)
                        {
                            $rule_arr["id"] = $id;
                        }
                    }
                    
                    if ($will_validate)
                    {
                        $vaildate_result = $validator->{$rule}($this->data[$field], $rule_arr);

                        if (!$vaildate_result)
                        {
                            $this->validationErrors[$field] = $rule_arr["msg"];
                        }
                    }
                }
            }
        }
        
        return empty($this->validationErrors);
    }
    
    protected function beforeSave()
    {
        return true;
    }
    
    protected function getValidator()
    {
        return Validation\Validator::getInstance();
    }
    
    protected function getValidationRules()
    {
        return array();
    }
    
    protected function getTableFieldList()
    {
        $table = $this->getTable();
        if (isset(self::$tableInfo[$table]["fields"]))
        {
            return self::$tableInfo[$table]["fields"];
        }
        
        $db = $this->getDataSource();
        
        $fields_info = $db->getFieldInfo($table);
        
        if ($fields_info === false)
        {
            throw new Exception("unable to get fields of table " . $table);
        }
        
        $list = array();
        
        foreach($fields_info as $field)
        {
            $list[$field->name] = $field->type;
        }
        
        self::$tableInfo[$table]["fields"] = $list;
        
        return $list;
    }
}

