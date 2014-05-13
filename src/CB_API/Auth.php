<?php
namespace CB_API;

class Auth extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	public function verify()
	{
		if ($this->verified) {
            output(array(
                'status' => 'verified',
                'success' => true
            ));
		} else {
            output(array(
                'status' => 'unverified',
                'success' => false
            ));
		}
	}
}