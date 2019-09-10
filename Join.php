<?php
namespace PhpDatabaseModel;

class Join
{
    public $model, $key, $alias, $qs;
    public function __construct(Model $model, $key, $alias = null)
    {
        $this->model = $model;
        $this->key = $key;
        
        if ($alias)
        {
            $this->alias = $alias;
        }
        else
        {
            $this->alias = $model->className;
        }
        
        $this->qs = new \QueryBuilder\QuerySelect($model->getTable(), $this->alias);
    }
    
    public static function init(Model $model, $key, $alias = null)
    {
        return new self($model, $key);
    }
}
