<?php
/**
 * Get RWDB Instance
 * @return instance
 */
function dbInstance()
{
    return new RWDB();
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

    $sth = dbInstance()->db->prepare(trim($sql));
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