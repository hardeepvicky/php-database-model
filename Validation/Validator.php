<?php
namespace PhpDatabaseModel\Validation;

class Validator
{
    protected $model;
    protected static $instance;
    
    public function __construct(\PhpDatabaseModel\Model $model)
    {
        $this->model = $model;
    }
    
    public static function getInstance(\PhpDatabaseModel\Model $model)
    {
        if (!self::$instance)
        {
            self::$instance = new self($model);
        }
        
        return self::$instance;
    }
    
    public function isNotEmpty($value, $args)
    {
        return !empty($value);
    }
    
    public function isUnique($value, $args)
    {
        if (!isset($args["fieldName"]))
        {
            throw new Exception("Validator : fieldName must be set in args");
        }
        
        if (!isset($args["fieldValue"]))
        {
            throw new Exception("Validator : fieldValue must be set in args");
        }
        
        $wh = \QueryBuilder\Where::init("AND")->add($args["fieldName"], $args["fieldValue"]);
        
        if (isset($args["id"]) && $args["id"])
        {
            $wh->addWhere(\QueryBuilder\Where::init("NOT")->add("id", $args["id"]));
        }
        
        $c = $this->model->selectCount($wh);
        
        return $c === 0;
    }
    
    public function isMax($value, $args)
    {
        if (!isset($args["maxValue"]))
        {
            throw new Exception("Validator : maxValue must be set in args");
        }
        
        return $value > $args["maxValue"];
    }
    
    public function isMin($value, $args)
    {
        if (!isset($args["minValue"]))
        {
            throw new Exception("Validator : minValue must be set in args");
        }
        
        return $value < $args["minValue"];
    }
    
    public function isEmail($value, $args)
    {
        
    }
    
    public function isNumber($value, $args)
    {
        
    }
    
    public function isAlphabet($value, $args)
    {
        
    }
    
    public function isAlphaNumeric($value, $args)
    {
        
    }
    
    public function isDate($value, $args)
    {
        
    }
    
    public function isTime($value, $args)
    {
        
    }
}
