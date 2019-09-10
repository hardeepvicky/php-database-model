<?php
namespace PhpDatabaseModel;

class Extract
{
    public static function extract ($data, $path)
    {
        if (!$path)
        {
            return false;
        }
        
        $paths = explode(".", $path);
        
        $ret = array();
        
        foreach($data as $k => $arr)
        {
            if (count($paths) > 0)
            {                
                if ($k == $paths[0] || $paths[0] == "{n}")
                {
                    if (is_array($arr))
                    {
                        $new_paths = $paths;
                        unset($new_paths[0]);
                        if (!empty($new_paths))
                        {
                            $new_path = implode(".", $new_paths);
                            $ret = array_merge($ret, self::extract($arr, $new_path) );
                        }
                        else
                        {
                            $ret[] = $arr;
                        }
                    }
                    else if (count($paths) == 1)
                    {
                        $ret[] = $arr;
                    }
                }
            }
        }
        
        return $ret;
    }
}
