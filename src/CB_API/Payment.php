<?php
namespace CB_API;

class Payment extends Base
{
    const PAYMENT_ID = 5;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function pay()
    {
        $datetime = new \DateTime();
        
        $data_fields = array();
        
        $data_fields['firstName'] = filter_input(0, 'firstName', FILTER_SANITIZE_STRING);
        $data_fields['lastName'] = filter_input(0, 'lastName', FILTER_SANITIZE_STRING);
        
        $data_fields['creditCardType'] = filter_input(0, 'creditCardType', FILTER_CALLBACK, array(
            'options' => function ($value) {
                return (in_array($value, array(
                    'Visa', 'MasterCard', 'Discover', 'Amex'
                ))) ? $value : false;
            }
        ));
        
        $data_fields['creditCardNumber'] = filter_input(0, 'creditCardNumber', FILTER_SANITIZE_STRING);
        
        $data_fields['expDateMonth'] = filter_input(0, 'expDateMonth', FILTER_CALLBACK, array(
            'options' => function ($value) {
                return (in_array($value, month_range(), true))
                ? $value : false;
            }
        ));
        
        $data_fields['expDateYear'] = filter_input(0, 'expDateYear', FILTER_CALLBACK, array(
            'options' => function ($value) use($datetime) {
                return (in_array($value, range((int) $datetime->format('Y'), (int) $datetime->format('Y') + 10)))
                ? $value : false;
            }
        ));
        
        $data_fields['cvv2Number'] = filter_input(0, 'cvv2Number', FILTER_SANITIZE_STRING);
        $data_fields['address1'] = filter_input(0, 'address1', FILTER_SANITIZE_STRING);
        $data_fields['city'] = filter_input(0, 'city', FILTER_SANITIZE_STRING);
        $data_fields['state'] = filter_input(0, 'state', FILTER_SANITIZE_STRING);
        $data_fields['zip'] = filter_input(0, 'zip', FILTER_VALIDATE_INT);
        $data_fields['country'] = filter_input(0, 'country', FILTER_SANITIZE_STRING);
        $data_fields['stripeToken'] = filter_input(0, 'stripeToken', FILTER_SANITIZE_STRING);
        $data_fields['coursecost'] = filter_input(0, 'coursecost', FILTER_SANITIZE_STRING);
        $data_fields['scheduledcoursesid'] = filter_input(0, 'scheduledcoursesid', FILTER_VALIDATE_INT);
        
        $data_fields = array_map('trim', $data_fields);
        
        $errors = "";
        
        if (!isset_empty($data_fields)) {
            $errors = "Required fields are missing.";    
        }
        
        $course_id = exist_in(array(
            'table' => 'scheduledcourses',
            'where_column' => 'scheduledcoursesid',
            'where_value' => $data_fields['scheduledcoursesid'],
            'where_datatype' => \PDO::PARAM_INT
        ));
        
        if (!$course_id) {
            $errors = "Course doesn't exists.";
        }
        
        if (!empty($errors)) {
            output_error(self::PAYMENT_ID, $errors);
        }
        
        $user_data = get_user_info();
        
        try {
            require_once ABSPATH . 'assets/external_files/stripe_payment/lib/Stripe.php';
            Stripe::setApiKey('sk_test_E7PndfA06ZwUe4GjANBr0Acf');

			$charge = Stripe_Charge::create(array(
				"amount" 				=> $data_fields['coursecost'],
				"currency" 				=> "usd",
				"card" 					=> $data_fields['stripeToken'],
				"description" 			=> $user_data['studentemddress']
			));
            
            if ($charge->paid) {
                $updated = $this->updateInto('courseregistrations', array(
					"sessionid"							=> Session::get('session_id'),
					"paymenttransactionid"				=> $charge->id,
					"paymentstatus"						=> 'paid',
					"paymenttype"						=> "stripe",
					"total_amount"						=> $data_fields['coursecost'],
					"total_product"						=> 1,
					"amount"							=> $data_fields['coursecost'],
					"form_data"							=> serialize($data_fields)
                ), array(
                    'scheduledid' => $data_fields['scheduledcoursesid'],
                    'studentid' => Session::get('id')
                ));
                
                if ($updated) {
                    $inserted = $this->insertInto("payment_record", array(
                        'TRANSACTIONID' => $charge->id,
                        'studentid' => $user_data['id'],
                        'token_id' => $charge->id
                    ));
                    
                    if ($inserted) {
                        output_success(self::PAYMENT_ID, 'Payment successfuly made.');
                    }
                }
            }
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
        
        output_success(self::PAYMENT_ID);
    }
}