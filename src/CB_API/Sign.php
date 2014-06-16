<?php
namespace CB_API;

class Sign extends Base
{
    const SIGN_ID = 1;
    
	public function __construct()
	{
		parent::__construct();
	}

	public function in()
	{
        $email = filter_input(0, 'cb_login_email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(0, 'cb_login_password', FILTER_SANITIZE_STRING);
        
        $student = exist_in(array(
            'table' => 'students',
            'where_column' => array('studentemddress', 'studentpassword'),
            'where_value' => array($email, $password)
        ));
        
        if (!$student) {
            output_error(self::SIGN_ID, "Account doesn't exist.");
        }
        
        Session::set(array(
            'id' => $student['studentsid'],
            'email' => $student['studentemddress']
        ));
        
        $session_id = session_id();
        
        $student_reg = $this->insertInto('api_user_sessions', array (
            'session_id' => $session_id,
            'user_id' => $student['studentsid']
        ));
        
        output_success(self::SIGN_ID, "Logged In.", array('session_id' => $session_id));
	}
    
    public function up()
	{
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
            output_error(self::SIGN_ID, "Please first enroll for the course");
        }
        
        $errors = "";
        
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
            $errors = "Sorry, but the course doesn't exist. This mostly because you didn't enroll the course in the first place.";
        } else if (!isset(  $std_name,
                            $std_lastname,
                            $std_password,
                            $std_password2,
                            $std_email,
                            $std_address,
                            $std_zip
        )) {
            $errors = "Required fields are missing.";    
        } else if ($email_exist) {
            $errors = "Email already exist.";
        }
        
        if (!empty($errors)) {
            output_error(self::SIGN_ID, $errors);
        }

        $student_reg = $this->insertInto('students', array (
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

        if ($student_reg && $this->lastInsertId()) {
            $course_reg = $this->insertInto('courseregistrations', array (
                'scheduledid' => $course_id,
                'studentid' => $this->lastInsertId(),
                'registrationstatus' => 'registered',
                'paymentstatus' => 'not paid',
                'registeredby' => 'selfregister',
                'total_amount' => 0,
                'total_product' => 0,
                'form_data' => ''
            ));
            
            if ($course_reg) {
                output_success(self::SIGN_ID, 'Registration completed. You can now login.');
            }
        }
	}
    
    public function out()
    {
        if (!Pluggable::userin()) {
            send_header(401);
        }
        
        $status = $this->updateInto('api_user_sessions', array (
            'status' => 'off'
        ), array (
            'session_id' => '"' . get_session_id() . '"',
            'user_id' => get_user_id()
        ));

        if ($status) {
            output_success(self::SIGN_ID);
        }
        
        output_error(self::SIGN_ID);
    }
}