<?php
// Send header
function send_header($code)
{
    $msg = '';

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
		"code" => $code
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
	if ($echo)
		echo json_encode($str);

	return json_encode($str);
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
        SELECT corpapikey
        FROM corp_admins
        WHERE corpuseremail = :email
        LIMIT 1
    ";
    
    $sth = $rw->db->prepare(trim($sql));
    $sth->bindParam(':email', $email, PDO::PARAM_STR);
    
    if ($sth->execute()) {
        $results = $sth->fetch(PDO::FETCH_ASSOC);
        
        return $results['corpapikey'];
    }
    
    return false;
}

function get_courses_listing()
{
    global $rw;
    
    $all_listing = array();
    
    $sql = "
        SELECT 
          sc.coursetype,
          sc.coursedate,
          sc.scheduledcoursesid,
          CONCAT_WS(', ', loc.locationcity, loc.locationstate) location,
          ct.coursetypename course,
          CONCAT_WS(' ', ins_c.ins_certification_name, ct.coursetypename) coursename,
          loc.locationzip,
          loc.lat AS lat,
          loc.lng AS lon,
          loc.locationaddress address,
          ct.coursetypename,
          ct.comments,
          ct.coursetypecert,
          sc.courseinstructor,
          sc.coursedate,
          sc.coursecost,
          sc.notes,
          sc.coursetime,
          ca.inst_cert_agency_name agency,
          sc.coursenumberofseats totalseats,
          (sc.coursenumberofseats - CAST(COUNT(cr.courseregistrationsid) AS UNSIGNED)) remainingseats
        FROM
          scheduledcourses sc 
          RIGHT JOIN locations loc 
            ON sc.courselocationid = loc.locationsid 
          RIGHT JOIN coursetypes ct 
            ON sc.coursetype = ct.coursetypesid
          INNER JOIN instructor_cert_types ins_c
	        ON ct.coursetypecert = ins_c.ins_cert_id
          INNER JOIN courseregistrations cr
	        ON sc.scheduledcoursesid = cr.scheduledid AND cr.registrationstatus != 'cancelled'
          INNER JOIN instructor_cert_agency ca
            ON ct.coursetypecert = ca.inst_cert_agencyid
        WHERE 1 = 1
          AND sc.coursedate > CURDATE()
          AND sc.privatecourse = 'no'
          AND (sc.coursestatus = 'scheduled' OR sc.coursestatus = 'accepted')
        GROUP BY sc.scheduledcoursesid
    ";
    
    $sth = $rw->db->prepare(trim($sql));
    
    if ($sth->execute()) {
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);
        $sth->closeCursor();

        foreach ($results as $result) {
            $cert_type = get_certificate_type($result['coursetypecert']);
            
            $key = recursive_array_search($result['coursename'], $all_listing);
            
            if ($key !== false) {
                $all_listing[$key]['classes'][] = $result;
            } else {
                $all_listing[] = array(
                    'course' => array(
                        'course_name' => $result['coursename'],
                        'course_id' => $result['coursetype'],
                        'certificate' => $cert_type
                    ),
                    'classes' => array($result)
                );
            }
        }
        
        return $all_listing;
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