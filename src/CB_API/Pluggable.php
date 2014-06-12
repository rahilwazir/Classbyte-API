<?php
namespace CB_API;

class Pluggable
{
    public static function __callStatic($method, $args)
    {
        return call_user_func_array(__CLASS__ . "::" . $method, $args);    
    }
    
    protected static function userin()
    {
        if (isset($_COOKIE['__cbapi'])) {
            $session_id = $_COOKIE['__cbapi'];
            
            $cookie_session_exist = exist_in(array(
                'table' => 'api_user_sessions',
                'where_column' => 'session_id',
                'where_value' => $session_id
            ));
            
            if ($cookie_session_exist) {
                return true;
            }
        }
        
        return false;
    }
}