<?php
/**
 * Get RWDB Instance
 * @return instance
 */
function dbInstance()
{
    return new RWDB();
}

/**
 * Output html selected attribute
 * @param string $static
 * @param string $current
 * @param boolean $echo
 * @return string
 */
function selected($static, $current, $echo = true)
{
    if ((string) $static === (string) $current) {
        if ($echo) {
            echo 'selected="selected"';
        } else {
            return 'selected="selected"';
        }        
    }
    
    return '';
}

/**
 * Check if input is valid date. e.g: 12-12-2014
 * @param string $date
 * @return boolean
 */
function rw_is_date($date)
{
    $date = preg_split('/[\/|-]/', $date);

    $date = array_filter(array_map('intval', $date));
    
    if ( !empty($date) && checkdate($date[0], $date[1], $date[2]) ) {
        return true;
    }

    return false;
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

function array2csv(array &$array)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   
   // fputcsv($df, array_keys(reset($array)));
   
   foreach ($array as $row) {
      fputcsv($df, $row);
   }
   fclose($df);
   return ob_get_clean();
}

/**
 * Check if request is ajax
 * @return boolean
 */ 
function is_request_ajax()
{
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ? true : false;
}

/**
 * Retrieve course status
 * @param int $course_id
 * @return boolean
 */ 
 
function get_course_status($course_id)
{
    $id = intval($course_id);
    
    return exist_in(array(
        'select' => 'coursestatus',
        'table' => 'scheduledcourses',
        'where_column' => 'scheduledcoursesid',
        'where_value' => $id,
        'where_datatype' => PDO::PARAM_INT
    ));
}

/**
 * Check if course is already cancel
 * @param int $course_id
 * @return boolean
 */ 
function is_course_cancel($course_id)
{
    $status = get_course_status($course_id);
    
    if ($status === "cancelled") {
        return true;
    }
    
    return false;
}

/**
 * Change course status
 * @param int $course_id
 * @param string $course_status_to
 * @return boolean
 */ 
function change_course_status($course_id, $course_status_to)
{
    $cur_status = get_course_status($course_id);
    
    if ( ((string) $cur_status === (string) $course_status_to) ) {
        return false;
    }
    
    $sql = "UPDATE course_status
			SET status_from = :status_from, status_to = :status_to
			WHERE course_id = :course_id";

    $sp = dbInstance()->prepare($sql);
    $sp->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $sp->bindParam(':status_from', $cur_status, PDO::PARAM_STR);
    $sp->bindParam(':status_to', $course_status_to, PDO::PARAM_STR);
    
    if ($sp->execute()) {
        return true;
    }
    
    return false;
}

// Send header
function send_header($code, $msg = '')
{
    switch ($code) {
        case 401:
            $code = 401;
            $msg = $code . ' Unauthorized Access';
            break;
        case 404:
            $code = 404;
            $msg = $code . ' Not Found';
            break;
        case 403:
            $code = 403;
            $msg = $code . ' Forbidden';
            break;
        default:
            break;
    }

	header("HTTP/1.1 {$msg}");
    
    error_log($msg, 0);
    
	output(array(
		"error" => $msg,
		"status" => $code
	));
    
	exit;
}

function disable_errors($disable = false)
{
    if ($disable) {
        error_reporting(E_ALL);
        return;
    }
    
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Log error reporting
function log_error($msg = '')
{
	if (!empty($msg)) {
		error_log($msg, 0);
	}
}

/**
 * Convert underscore string to camelCase string
 * @param string $str
 * @return string
 */
function convert_to_cc($str)
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
}

/**
 * Output as json encode
 * Sluggish helper, improve if you can
 */
function output($str, $echo = true)
{
	if ($echo) {
	   echo json_encode($str);
       exit;
	} else {
	   return json_encode($str);
	}
}

function output_error($action, $msg = null, $data = null)
{
    if (!empty($action)) {
        output(array(
            'success' => false,
            'action' => $action,
            'message' => $msg,
            'object' => $data
        ));
    }
}

function output_success($action, $msg = null, $data = null)
{
    if (!empty($action)) {
        output(array(
            'success' => true,
            'action' => $action,
            'message' => $msg,
            'object' => $data
        ));
    }
}

/**
 * =================================================================================
 * API Helpers
 * =================================================================================
 */
function getApiKey($email)
{
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
    return exist_in(array(
        'select' => 'apikey',
        'table' => 'instructors',
        'where_column' => 'instructoremail',
        'where_value' => $email
    ));
    
    if ($result) {        
        return $result['apikey'];
    }
    
    return false;
}

/**
 * @param string $table
 * @param array $where
 * @return string|bool
 */
function exist_in(array $data)
{
    if (!isset($data['limit'])) {
        $data['limit'] = 'LIMIT 1';
    }
    
    if (!isset($data['select'])) {
        $data['select'] = '*';
    }
    
    $where_column = $data['where_column'];
    $where_value = $data['where_value'];
    $relation = $data['relation'];
    
    if(!isset($relation)) {
        $relation = "AND";
    }
    
    $where = array();
    $is = '';
    
    if (isset_empty($where_column, $where_value)) {
        if (is_array($where_column) && is_array($where_value)) {
            
            $where = array_combine($where_column, $where_value);
            
            foreach ($where as $key => $value) {
                if (!empty($key) && !empty($value)) {
                    $is .= " {$relation} {$key} = :{$key}";
                }
            }
        } else {
            $is = "{$relation} {$where_column} = :val";
        }
    }
    
    $sql = "
        SELECT {$data['select']}
        FROM {$data['table']}
        WHERE 1=1 {$is}
        {$data['limit']}
    ";
    
    $sth = dbInstance()->prepare($sql);
    $pdo_type_const = (isset($data['where_datatype']) ? $data['where_datatype'] : PDO::PARAM_STR);
    
    if (!empty($where)) {
        foreach ($where as $key => &$value) {
            $sth->bindParam(":{$key}", $value, $pdo_type_const);
        }
    } else {
        $sth->bindParam(":val", $where_value, $pdo_type_const);
    }

    if ($sth->execute()) {
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        
        if ($data['select'] !== '*') {
            if (strpos($data['select'], ',') === false) {
                $return_val = $data['select'];
            }            
        }
        
        return (isset($return_val)) ? $result[ $return_val ] : $result;
    }
    
    return false;
}

function get_certificate_type($id)
{
    $id = intval($id);
    
    return exist_in(array(
        'select' => 'ins_certification_name',
        'table' => 'instructor_cert_types',
        'where_column' => 'ins_cert_id',
        'where_value' => $id
    ));
    
}

/**
 * =================================================================================
 * End of API Helpers
 * =================================================================================
 */
 
function recursive_array_search($needle,$haystack)
{
    foreach($haystack as $key=>$value) {
        $current_key=$key;
        if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
            return $current_key;
        }
    }
    return false;
}

/**
 * Source: http://stackoverflow.com/a/2595372/2706988
 */
function crypto_rand_secure($min, $max) {
    $range = $max - $min;
    if ($range < 0) return $min; // not so random...
    $log = log($range, 2);
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd >= $range);
    return $min + $rnd;
}

/**
 * Source: http://stackoverflow.com/a/2595372/2706988
 */
function getToken($length=32) {
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    for($i=0;$i<$length;$i++){
        $token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
    }
    return $token;
}

function get_session_id()
{
    if (!isset($_COOKIE['__cbapi'])) {
        return false;
    }

    return exist_in(array(
        'select' => 'session_id', 
        'table' => 'api_user_sessions',
        'where_column' => 'session_id',
        'where_value' => $_COOKIE['__cbapi']
    ));
}

function get_user_id()
{
    return (int) exist_in(array(
        'select' => 'user_id', 
        'table' => 'api_user_sessions',
        'where_column' => 'session_id',
        'where_value' => (get_session_id()) ? get_session_id() : ''
    ));
}

function get_user_info()
{
    $user_id = get_user_id();
    
    if (!$user_id) {
        return false;
    }
    
    return exist_in(array(
        'select' => 'studentsid id,
                    studentsname,
                    studentlastname,
                    studentaddress,
                    studentaddress2,
                    studentcity,
                    studentstate,
                    studentzip,
                    studentmobilephone,
                    studentemddress',
        'table' => 'students',
        'where_column' => 'studentsid',
        'where_value' => $user_id,
        'where_datatype' => PDO::PARAM_INT
    ));
}

function isset_empty()
{
    if (func_num_args() > 0) {
        $args = func_get_args();
    } else if (is_array(func_get_arg(0))) {
        $args = func_get_arg(0);
    }
    
    if (isset($args)) {
        foreach($args as $arg) {
            if (!isset($arg) || empty($arg))
                return false;
        }
    }
        
    return true;
}

function month_range()
{
    $month_range = array();
    foreach (range(1, 12) as $r) {
        $month_range[] = sprintf("%02d", $r);
    }
    
    return $month_range;
}

function course_exist($id)
{
    return exist_in(array(
        'table' => 'scheduledcourses',
        'where_column' => 'scheduledcoursesid',
        'where_value' => $id,
        'where_datatype' => \PDO::PARAM_INT
    ));
}

function course_enrolled($course_id, $user_id)
{
    return exist_in(array(
        'table' => 'courseregistrations',
        'where_column' => array('scheduledid', 'studentid'),
        'where_value' => array($course_id, $user_id),
        'where_datatype' => \PDO::PARAM_INT
    ));
}

function get_stripe_api_key()
{
    return exist_in(array(
        'select' => 'stripe_private_key',
        'table' => 'config_settings',
        'where_column' => 'payment',
        'where_value' => 'stripe'
    ));
}

function is_paid_to_course($course_id, $user_id)
{
    return exist_in(array(
        'table' => 'courseregistrations',
        'where_column' => array('scheduledid', 'studentid', 'paymentstatus'),
        'where_value' => array($course_id, $user_id, 'paid')
    ));
}