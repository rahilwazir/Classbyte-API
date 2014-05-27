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

	output(array(
		"error" => $msg,
		"status" => $code
	));
	
	error_log($msg, 0);

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
	} else {
	   return json_encode($str);
	}
    exit;
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
    
    $sql = "
        SELECT {$data['select']}
        FROM {$data['table']}
        WHERE {$data['where_column']} = :val
        {$data['limit']}
    ";

    $sth = dbInstance()->prepare(trim($sql));
    $pdo_type_const = (isset($data['where_datatype']) ? $data['where_datatype'] : PDO::PARAM_STR);
    $sth->bindParam(':val', $data['where_value'], $pdo_type_const);

    if ($sth->execute()) {
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        
        if ($data['select'] !== '*') {
            $return_val = $data['select']; 
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