<?php
namespace PhpDatabaseModel;

class CacheConfig
{
    public $group;
    public $seconds;
    
    public function __construct($seconds, $group = null)
    {
        $this->seconds = $seconds;
        $this->group = $group;
    }
}

class Cache
{
    private $config, $path;
    
    public function __construct(CacheConfig $config)
    {
        $this->config = $config;
        
        $this->path = __DIR__  . "/cache_files/";
        if ($config->group)
        {
            $config->group = $this->cleanFileName($config->group);
            $this->path .= $config->group . "/";
        }
        
        if (!file_exists($this->path))
        {
            mkdir($this->path, 0777, true);
        }
    }
    
    public function write($name, $data)
    {
        $file = $this->path . $this->cleanFileName($name);
        
        $seconds = time() + $this->config->seconds;
        $string = $seconds . "\n" . serialize($data);
        
        file_put_contents($file, $string);
        return $file;
    }
    
    public function read($name)
    {
        $file = $this->path . $this->cleanFileName($name);
        
        if (!file_exists($file))
        {
            return null;
        }
        
        $data = array();
        $handle = fopen($file, "r");
        if ($handle) 
        {
            $i = 0;
            while (($line = fgets($handle)) !== false) 
            {
                if ($i == 0)
                {
                    $seconds = (int) $line;
                    
                    if ($seconds < time())
                    {
                        return false;
                    }
                }
                else
                {
                    $data = unserialize($line);
                }
                $i++;
            }

            fclose($handle);
        } 
        else 
        {
            return null;
        } 
        
        return $data;
    }
    
    private function cleanFileName($filename)
    {
        return preg_replace("/[^a-zA-Z0-9_-]+/", '-', $filename);
    }
}
