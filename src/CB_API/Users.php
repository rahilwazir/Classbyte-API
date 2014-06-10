<?php
namespace CB_API;

class Users extends Base
{
    const USERS_ID = 4;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function info()
    {
        if (Session::get('id') && Session::get('email')) {
            $user_info = get_user_info(Session::get('id'));
            
            output_success(self::USERS_ID, null, $user_info);
        }
        
        output_error(self::USERS_ID);
    }
}