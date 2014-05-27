<?php
namespace CB_API;

abstract class Base extends \RWDB
{
    protected $request_method = null;
    protected $verified = false;
    
	public function __construct()
    {
        parent::__construct();
        
        $this->requestMethod();
        
        $this->validate();
    }
    
    protected function validate()
    {
        if (!$this->request_method) {
            send_header(401);
        }
        
        $email = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        
        $key = getApiKey($email);

        if ($key && $key === $password) {
            $this->verified = true;
        } else {
            send_header(401);
        }
    }
    
    protected function requestMethod()
    {
        if ("POST" === $_SERVER['REQUEST_METHOD']) {
            $this->request_method = $_SERVER['REQUEST_METHOD'];
        }
    }
}