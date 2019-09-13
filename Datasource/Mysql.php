<?php
namespace PhpDatabaseModel\Datasource;
class Mysql implements Datasource
{
    protected static $instance;
    protected $conn, $database, $logs = [];
    public function __construct(Config $config)
    {
        $this->conn = mysqli_connect($config->host, $config->user, $config->password, $config->database);
        
        if (!$this->conn)
        {
            throw new Exception("Unable to connect $config->host with database $config->database" . PHP_EOL . "Exception : " . mysqli_connect_errno());
        }
    }
    
    public function delete($q)
    {
        return $this->_insert_update_delete($q, "delete");
    }

    public function insert($q)
    {
        return $this->_insert_update_delete($q, "insert");
    }
    
    public function update($q)
    {
        return $this->_insert_update_delete($q, "update");
    }
    
    private function _insert_update_delete($q, $log_type)
    {
        $log["type"] = $log_type;
        $log["query"] = $q;
        
        $st = microtime(true);
        
        $result = $this->query($q);
        
        $count = mysqli_affected_rows($this->conn);
        
        $time = microtime(true) - $st;
        $log["time_taken"] = round($time * 1000, 3);
        $log["count"] = count($records);
        
        $this->logs[] = $log;
        
        return $count > 0;
    }
    
    public function getLastInsertId()
    {
        return mysqli_insert_id($this->conn);
    }

    public function query($q)
    {
        return mysqli_query($this->conn, $q);        
    }

    public function select($q)
    {
        $log["type"] = "select";
        $log["query"] = $q;
        
        $st = microtime(true);
        
        $result = $this->query($q);
        
        $records = array();
        while ($row = mysqli_fetch_assoc($result))
        {   
            $records[] = $row;
        }
        
        $time = microtime(true) - $st;
        $log["time_taken"] = round($time * 1000, 3);
        $log["count"] = count($records);
        
        $this->logs[] = $log;
        
        return $records;
    }
    
    public function begin()
    {
        return $this->query("BEGIN TRANSACTION;");
    }
    public function commit()
    {
        return $this->query("COMMIT TRANSACTION;");
    }
    public function rollback()
    {
        return $this->query("ROLLBACK TRANSACTION;");
    }
    
    public function getLogs()
    {
        return $this->logs;
    }

    public function getInstance(Config $config)
    {
        if (!self::$instance)
        {
            self::$instance = new self($config);
        }
        
        return self::$instance;
    }

    public function getCleanName($name)
    {
        return "`" . $name . "`";
    }
    
    public function getFieldInfo($table)
    {
        $q = "SELECT * FROM " . $this->getCleanName($table);
        
        $log["type"] = "select";
        $log["query"] = $q;
        
        $st = microtime(true);
        if ( $result = mysqli_query($this->conn, $q))
        {        
            $info = $result->fetch_fields();

            $time = microtime(true) - $st;
            $log["time_taken"] = round($time * 1000, 3);
            $log["count"] = count($records);

            $this->logs[] = $log;

            return $info;
        }
        
        return false;
    }
}

