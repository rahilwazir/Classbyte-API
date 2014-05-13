<?php
namespace CB_API;

class Courses extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	public function listing()
	{
	   if (get_courses_listing()) {
	       output(get_courses_listing());
	   }
	}
}