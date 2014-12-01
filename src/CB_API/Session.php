<?php
namespace CB_API;

class Session
{
    public static function __callStatic($method, $args)
    {
        return call_user_func_array(__CLASS__ . "::" . $method, $args);    
    }
    
    protected static function get($key)
    {
        if (isset($_SESSION['user']) && !$key) {
            return $_SESSION['user'];
        }
        
        if (isset($_SESSION['user'][$key])) {
            return $_SESSION['user'][$key];
        }
        
        return false;
    }
    
    protected static function set($key, $value = '')
    {
        if (is_array($key) && !empty($key) && count($key) > 0) {
            foreach ($key as $k => &$v) {
                $_SESSION['user'][$k] = $v;
            }
        } else {
            $_SESSION['user'][$key] = $value;
        }
    }
    
    protected static function destroy()
    {
        unset($_SESSION['user']);
    }
}