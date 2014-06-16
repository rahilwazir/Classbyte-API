<?php
namespace CB_API;

use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\AddressType;
use PayPal\EBLBaseComponents\CreditCardDetailsType;
use PayPal\EBLBaseComponents\DoDirectPaymentRequestDetailsType;
use PayPal\EBLBaseComponents\PayerInfoType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\PersonNameType;
use PayPal\PayPalAPI\DoDirectPaymentReq;
use PayPal\PayPalAPI\DoDirectPaymentRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

class Payment extends Base
{
    const PAYMENT_ID = 5;
    
    private $form_fields;
    
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
        
        $this->form_fields = $data_fields;
        
        $this->process();
        
        output_error(self::PAYMENT_ID);
    }
    
    public function mode()
    {
        output_success(self::PAYMENT_ID, Pluggable::paymentMode());
    }
    
    protected function process()
    {
        if (Pluggable::paymentMode() === "stripe")
            $this->payStripeMode();
        elseif (Pluggable::paymentMode() === "paypal")
            $this->payPaypalMode();
    }
    
    private function payStripeMode()
    {
        try {
            $user_data = get_user_info();
            
            $data_fields = $this->form_fields;
            
            require_once ABSPATH . 'assets/external_files/stripe_payment/lib/Stripe.php';
            
            $api_key = get_stripe_api_key();
            $api_key = $api_key ? $api_key : '';
            
            \Stripe::setApiKey($api_key);

			$charge = \Stripe_Charge::create(array(
				"amount" 				=> ((int) $data_fields['coursecost'] * 100),
				"currency" 				=> "usd",
				"card" 					=> $data_fields['stripeToken'],
				"description" 			=> $user_data['studentemddress']
			));
            
            if ($charge->paid) {
                $this->updateInto('courseregistrations', array(
					"sessionid"							=> get_session_id(),
					"paymenttransactionid"				=> $charge->id,
					"paymentstatus"						=> 'paid',
					"paymenttype"						=> "stripe",
					"total_amount"						=> $data_fields['coursecost'],
					"total_product"						=> 1,
					"amount"							=> $data_fields['coursecost'],
					"form_data"							=> serialize($data_fields)
                ), array(
                    'scheduledid' => $data_fields['scheduledcoursesid'],
                    'studentid' => $user_data['id']
                ));
                
                $this->insertInto("payment_record", array(
                    'TRANSACTIONID' => $charge->id,
                    'studentid' => $user_data['id'],
                    'token_id' => $charge->id
                ));
                
                output_success(self::PAYMENT_ID, 'Payment successfuly made.');
            } else {
                output_error(self::PAYMENT_ID, 'Problem with Stripe.');
            }
            
        } catch(Exception $e) {
            output_error(self::PAYMENT_ID, $e->getMessage());
        }
    }
    
    private function payPaypalMode()
    {        
        try {
            $user_data = get_user_info();
            
            $data_fields = $this->form_fields;
            
            $firstName = $data_fields['firstName'];
            $lastName = $data_fields['lastName'];
            
            /*
             * shipping adress
            */
            $address = new AddressType();
            $address->Name = "$firstName $lastName";
            $address->Street1 = $data_fields['address1'];
            $address->Street2 = $user_data['studentaddress2'];
            $address->CityName = $data_fields['city'];
            $address->StateOrProvince = $data_fields['state'];
            $address->PostalCode = $data_fields['zip'];
            $address->Country = $data_fields['country'];
            $address->Phone = $user_data['studentmobilephone'];
            
            $paymentDetails = new PaymentDetailsType();
            $paymentDetails->ShipToAddress = $address;
            
            // Amount
            $paymentDetails->OrderTotal = new BasicAmountType('USD', $data_fields['coursecost']);
            
            // Notify URL
            if(isset($_REQUEST['notifyURL']))
            {
            	$paymentDetails->NotifyURL = $_REQUEST['notifyURL'];
            }
            
            $personName = new PersonNameType();
            $personName->FirstName = $firstName;
            $personName->LastName = $lastName;
            
            //information about the payer
            $payer = new PayerInfoType();
            $payer->PayerName = $personName;
            $payer->Address = $address;
            $payer->PayerCountry = $data_fields['country'];
            
            // CC Details
            $cardDetails = new CreditCardDetailsType();
            $cardDetails->CreditCardNumber = $data_fields['creditCardNumber'];
            
            $cardDetails->CreditCardType = $data_fields['creditCardType'];
            $cardDetails->ExpMonth = $data_fields['expDateMonth'];
            $cardDetails->ExpYear = $data_fields['expDateYear'];
            $cardDetails->CVV2 = $data_fields['cvv2Number'];
            $cardDetails->CardOwner = $payer;
            
            $ddReqDetails = new DoDirectPaymentRequestDetailsType();
            $ddReqDetails->CreditCard = $cardDetails;
            $ddReqDetails->PaymentDetails = $paymentDetails;
            
            $doDirectPaymentReq = new DoDirectPaymentReq();
            $doDirectPaymentReq->DoDirectPaymentRequest = new DoDirectPaymentRequestType($ddReqDetails);

            $paypalService = new PayPalAPIInterfaceServiceService(Pluggable::getAcctAndConfig());
            
            try {
            	$doDirectPaymentResponse = $paypalService->DoDirectPayment($doDirectPaymentReq);
            } catch (Exception $ex) {
            	output_error(self::PAYMENT_ID, $ex->getMessage());
            }
            
            if(isset($doDirectPaymentResponse, $doDirectPaymentResponse->TransactionID)) {
                $this->updateInto('courseregistrations', array(
					"sessionid"							=> get_session_id(),
					"paymenttransactionid"				=> $doDirectPaymentResponse->TransactionID,
					"paymentstatus"						=> 'paid',
					"paymenttype"						=> "paypal",
					"total_amount"						=> $data_fields['coursecost'],
					"total_product"						=> 1,
					"amount"							=> $data_fields['coursecost'],
					"form_data"							=> serialize($data_fields)
                ), array(
                    'scheduledid' => $data_fields['scheduledcoursesid'],
                    'studentid' => $user_data['id']
                ));
                
                $this->insertInto("payment_record", array(
                    'TRANSACTIONID' => $doDirectPaymentResponse->TransactionID,
                    'studentid' => $user_data['id'],
                    'token_id' => $doDirectPaymentResponse->CorrelationID
                ));
                
                output_success(self::PAYMENT_ID, 'Payment successfuly made.', $doDirectPaymentResponse);
            } else {
                output_error(self::PAYMENT_ID, 'Problem with Paypal.', $doDirectPaymentResponse);
            }
            
        } catch(Exception $e) {
            output_error(self::PAYMENT_ID, $e->getMessage());
        }
    }
    
    public function stripePublicKey()
    {
        $public_key = exist_in(array(
            'select' => 'stripe_published_key',
            'table' => 'config_settings',
            'where_column' => 'payment',
            'where_value' => 'stripe'
        ));
        
        if ($public_key) {
            output_success(self::PAYMENT_ID, null, array (
                'key' => $public_key
            ));
        }
        
        output_error(self::PAYMENT_ID);
    }
}