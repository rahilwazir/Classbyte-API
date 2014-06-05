<?php
namespace CB_API;

class Auth extends Base
{
    const AUTH_ID = 3;
    
	public function __construct()
	{
		parent::__construct();
	}
    
    public function userin()
    {
        if (Session::get('id') && Session::get('email')) {
            output_success(self::AUTH_ID);
        }
        
        output_error(self::AUTH_ID);
    }
    
    public function userout()
    {
        if (Session::get('id') && Session::get('email')) {
            Session::destroy();
            output_success(self::AUTH_ID);
        }
        
        output_error(self::AUTH_ID);
    }
    
	public function verify()
	{
		if ($this->verified) {
            output_success(self::AUTH_ID, 'verified');
		}
        
        output_error(self::AUTH_ID, 'unverified');
	}
}