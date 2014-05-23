<?php
namespace CB_API;

class Sign extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	public function in()
	{
        
	}
    
    public function up()
	{
        global $rw;
        
        // Form fields
        $course_id = filter_input(0, 'course_id', FILTER_SANITIZE_NUMBER_INT);
        $std_name = filter_input(0, 'studentsname', FILTER_SANITIZE_STRING);
        $std_lastname = filter_input(0, 'studentlastname', FILTER_SANITIZE_STRING);
        $std_address = filter_input(0, 'studentaddress', FILTER_SANITIZE_STRING);
        $std_address2 = filter_input(0, 'studentaddress2', FILTER_SANITIZE_STRING);
        $std_city = filter_input(0, 'studentcity', FILTER_SANITIZE_STRING);
        $std_state = filter_input(0, 'studentstate', FILTER_SANITIZE_STRING);
        $std_zip = filter_input(0, 'studentzip', FILTER_SANITIZE_NUMBER_INT);
        $std_phone = filter_input(0, 'studentphone', FILTER_SANITIZE_STRING);
        $std_mobilephone = filter_input(0, 'studentmobilephone', FILTER_SANITIZE_STRING);
        $std_email = filter_input(0, 'studentemddress', FILTER_SANITIZE_EMAIL);
        $std_password = filter_input(0, 'studentpassword');
        $std_password2 = filter_input(0, 'studentpassword2');

        if (!isset($course_id)) {
            return;
        }
        
        $errors = array();
        
        $course_id_exist = exist_in(array(
            'table' => 'scheduledcourses',
            'where_column' => 'scheduledcoursesid',
            'where_value' => $course_id,
            'where_datatype' => \PDO::PARAM_INT
        ));
        
        $email_exist = exist_in(array(
            'table' => 'students',
            'where_column' => 'studentemddress',
            'where_value' => $std_email,
        ));
        
        if (!$course_id_exist) {
            $errors['errors'][] = "Sorry, but the course doesn't exist.";
        } else if (!isset($std_name, $std_lastname, $std_password, $std_password2, $std_email, $std_address, $std_zip)) {
            $errors['errors'][] = "Required fields are missing.";    
        } else if ($email_exist) {
            $errors['errors'][] = "Email is already exist.";
        }
        
        $errors = array_filter($errors);
        
        if (!empty($errors)) {
            output($errors);
        }

        $out = $rw->insertInto('students', array (
            'studentsname' => $std_name,
            'studentlastname' => $std_lastname,
            'studentaddress' => $std_address,
            'studentaddress2' => $std_address2,
            'studentcity' => $std_city,
            'studentstate' => $std_state,
            'studentzip' => $std_zip,
            'studentphone' => $std_phone,
            'studentmobilephone' => $std_mobilephone,
            'studentemddress' => $std_email,
            'studentpassword' => $std_password,
            'dateadded' => strtotime(date('d-m-y')),
            'status' => 'active',
            'addedby' => 'SelfRegister',
            'ipaddress' => $_SERVER['REMOTE_ADDR'],
            'studentparent' => 1
        ));

        if ($out) {
            output(array($rw->lastInsertId()));
        }
	}
}