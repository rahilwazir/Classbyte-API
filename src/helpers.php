<?php
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
}

/**
 * =================================================================================
 * API Helpers
 * =================================================================================
 */
function getApiKey($email)
{
    global $rw;

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    $sql = "
        SELECT apikey
        FROM instructors
        WHERE instructoremail = :email
        LIMIT 1
    ";
    
    $sth = $rw->db->prepare(trim($sql));
    $sth->bindParam(':email', $email, PDO::PARAM_STR);
    
    if ($sth->execute()) {
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        
        return $result['apikey'];
    }
    
    return false;
}

function get_certificate_type($id)
{
    global $rw;

    $id = intval($id);
    
    $sql = "
        SELECT ins_certification_name
        FROM instructor_cert_types
        WHERE ins_cert_id = :id
    ";
    
    $sth = $rw->db->prepare(trim($sql));
    $sth->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($sth->execute()) {
        $results = $sth->fetch(PDO::FETCH_ASSOC);
        
        return $results['ins_certification_name'];
    }
    
    return false;
    
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