<?php
namespace CB_API;

class Course extends Base
{
    const COURSE_ID = 10;
    
	public function __construct()
	{
		parent::__construct();
	}
    
    public function enroll()
    {
        if (!Pluggable::userin()) {
            send_header(401);
        }
        
        $user_id = get_user_id();
        
        $data_fields = array();
        $courseID = $data_fields['course_id'] = filter_input(0, 'course_id', FILTER_VALIDATE_INT);
        
        $course_reg = Pluggable::enroll($user_id, $courseID, $this);

        if ($course_reg) {
            output_success(self::COURSE_ID, 'You have been enrolled to the course.');
        } else {
            output_success(self::COURSE_ID, 'You have already enrolled for this course.');
        }

        output_error(self::COURSE_ID);
    }

    public function listing()
    {
        $sql = "SELECT
              sc.coursetype,
              sc.coursedate,
              sc.scheduledcoursesid,
              CONCAT_WS(', ', loc.locationcity, loc.locationstate) location,
              ct.coursetypename course,
              CONCAT_WS(' ', ins_cg.inst_cert_agency_name, ct.coursetypename) coursename,
              loc.locationzip,
              loc.lat AS lat,
              loc.lng AS lon,
              loc.locationaddress address,
              loc.locationaddress2 address2,
              loc.locationname,
              ct.coursetypename,
              ct.comments,
              ct.coursetypecert,
              sc.courseinstructor,
              sc.coursedate,
              sc.coursecost,
              sc.notes,
              sc.coursetime,
              sc.courseendtime,
              ca.inst_cert_agency_name agency,
              sc.coursenumberofseats totalseats,
              (sc.coursenumberofseats - CAST(COUNT(cr.courseregistrationsid) AS UNSIGNED)) remainingseats
            FROM
              scheduledcourses sc 
              RIGHT JOIN locations loc 
                ON sc.courselocationid = loc.locationsid 
              RIGHT JOIN coursetypes ct 
                ON sc.coursetype = ct.coursetypesid
              INNER JOIN instructor_cert_agency ins_cg
    	        ON ct.coursetypecert = ins_cg.inst_cert_agencyid
              LEFT JOIN courseregistrations cr
    	        ON sc.scheduledcoursesid = cr.scheduledid AND cr.registrationstatus != 'cancelled'
              INNER JOIN instructor_cert_agency ca
                ON ct.coursetypecert = ca.inst_cert_agencyid
            WHERE 1 = 1
              AND sc.coursedate > CURDATE()
              AND sc.privatecourse = 'no'
              AND (sc.coursestatus = 'scheduled' OR sc.coursestatus = 'accepted')
            GROUP BY sc.scheduledcoursesid";

        $results = $this->getResults(array(
            'sql' => $sql
        ));
        
        if ($results) {
            $all_listing = array();
            
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
            
            output($all_listing);
        }
    }
    
    public function paid($course_sche_id)
    {
        if (!Pluggable::userin()) {
            send_header(401);
        }
        
        $paid = exist_in(array(
            'table' => 'courseregistrations',
            'where_column' => array('scheduledid', 'studentid', 'paymentstatus'),
            'where_value' => array($course_sche_id, Session::get('id'), 'paid')
        ));

        if ($paid) {
            output_success(self::COURSE_ID, 'You already paid for this course.');
        }
        
        output_error(self::COURSE_ID, 'You haven\'t registered for this course or the course or you (student) doesn\'t exist.');
    }
    
    public function history()
    {
        if (!Pluggable::userin()) {
            send_header(401);
        }
        
        $sql = "SELECT
              cr.amount,
              cr.paymentstatus,
              cr.discountedamount,
              cr.paymenttype,
              sc.coursedate,
              sc.coursetime,
              sc.courseendtime,
              cr.registrationstatus,
              sc.scheduledcoursesid,
              sc.coursecost,
              ct.coursetypename,
              cr.registrationdatetime,
              cr.promocode,
              cr.courseregistrationsid,
              sc.coursestatus,
              l.locationname,
              l.locationaddress,
              l.locationcity,
              l.locationstate,
              l.locationzip,
              ca.inst_cert_agency_name agency
            FROM
              courseregistrations cr
              INNER JOIN scheduledcourses sc
                ON (cr.scheduledid = sc.scheduledcoursesid)
              INNER JOIN coursetypes ct
                ON (sc.coursetype = ct.coursetypesid)
              LEFT JOIN locations l
                ON (sc.courselocationid = l.locationsid)
              INNER JOIN instructor_cert_agency ca
                ON ct.coursetypecert = ca.inst_cert_agencyid
            WHERE sc.coursestatus != 'closed'
              AND studentid = " . get_user_id() . "
            GROUP BY sc.scheduledcoursesid";
        
        $result = $this->getResults(array(
            'sql' => $sql
        ));

        if ($result) {
            output_success(self::COURSE_ID, null, $result);
        }
        
        output_error(self::COURSE_ID);
    }
}
