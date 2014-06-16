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
    
    protected static function paymentMode()
    {
        return exist_in(array(
            'select' => 'payment',
            'table' => 'config_settings'
        ));
    }
    
    protected static function getAcctAndConfig()
    {
        $paypal_config = exist_in(array(
            'select' => 'api_username, api_password, api_signature',
            'table' => 'config_settings'
        ));
        
        if (!$paypal_config)
            return array(); 
        
    	$config = array(
			"acct1.UserName" => $paypal_config['api_username'],
			"acct1.Password" => $paypal_config['api_password'],
			"acct1.Signature" => $paypal_config['api_signature'],
            "mode" => "sandbox"
		);
		
		return $config;
    }
}