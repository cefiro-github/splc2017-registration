<?php
/*
Plugin Name: SPLC Registration
Plugin URI:  https://www.splc2017.net
Description: Plugin for registration
Version:     20170520
Author:      Antonio Manuel Gutierrez Fernandez
Author URI:  https://cefiro.org/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wporg
Domain Path: /languages
*/
include_once("functions.php");
//Variables
include_once("splcOptions.php");
//Methods
include_once("db-users.php");
include_once("db-codes.php");

include_once("listFunctions.php");

include_once("auxFunctions.php");
include_once("emailFunctions.php");
//Views
include_once("registration-form.php");
include_once("confirmation-form.php");
include_once("confirmed-form.php");
include_once("payment-reception.php");


register_activation_hook( __FILE__, 'splcregistration_install' );

add_shortcode( 'cr_splcregistration', 'splc_registration_shortcode' );

add_shortcode( 'sc_splcregistrationecl', 'splc_ecl_confirm' );
add_shortcode( 'sc_splceasychairlist', 'splc_easychair_list' );
add_shortcode( 'sc_splcbudget', 'splc_budget_view' );


wp_register_style('reg_style', plugins_url('/css/form.css',__FILE__ ));
wp_enqueue_style( 'reg_style');
wp_register_style('jquery_ui_themes', plugins_url('/css/jquery-ui.min.css',__FILE__ ));
wp_enqueue_style( 'jquery_ui_themes');


function splc_registration_shortcode() {
    ob_start();
    custom_registration_function();
    return ob_get_clean();
}

function splc_ecl_confirm(){
    processActionConfirm();
    if (!isset($_GET["detail"]))
        echo getTableHtmlAllUnpaid();
    else echo getTableHtml($_GET["detail"]);
}

function splc_budget_view(){
    echo getTableHtmlBudget();
}


function splc_easychair_list(){
    echo getTableHtmlEasyChair();
}

function custom_registration_function() {
    //print_r($_POST);
    global $studentToken;
    $student = false;
    if ($_GET["student"]==$studentToken){
        $student = true;
    }
    $registration_info  = array();
    $idRegistration     = 0;
    $status             = "registrationform";
    $params             = array();
    //print_r($_POST);
    if(isset($_GET["hash"])){
        switch($_GET["hash"]){
            case "sponsorcode1":
                $params["customPrice"] = 170;
                $params["disableRegOption"] = true;
                $params["specialCode"] = "Sponsored Price";
                
                break;
            case "keynote1":
                $params["customPrice"] = 0;
                $params["disableRegOption"] = true;
                $params["specialCode"] = "Keynote Registration";
                $params["defaultoption"]    = 5;
                break;

            case "volunteerstudent1":
                $params["customPrice"] = 390;
                $params["disableRegOption"] = true;
                $params["specialCode"] = "Volunteer Student";
                break;
            case "myraextra1":
                $params["customPrice"]      = 410;
                $params["disableRegOption"] = true;
                $params["defaultoption"]    = 4;
                $params["specialCode"]      = "Registration adjust";
                break;
			case "mipaward1":
                $params["customPrice"]      = 395;
                $params["disableRegOption"] = true;
                $params["defaultoption"]    = 5;
                $params["specialCode"]      = "MIP Award Registration";
                break;   				
            //$idRegistration = "100000";
            //$registration_info["totalamount"] = 690 - $registration_info["totalamount"];
            //$idRegistration = complete_registration($registration_info);
        }
    }
    if ( isset($_POST['submit'] ) ) {
        // sanitize user form input

        $firstname      =   sanitize_text_field( $_POST['firstname'] );
        $lastname       =   sanitize_text_field( $_POST['lastname'] );
        $affiliation    =   sanitize_text_field( $_POST['affiliation'] );
        $profile        =   sanitize_text_field( $_POST['profile'] );
        $address        =   sanitize_text_field( $_POST['address'] );
        $postcode       =   sanitize_text_field( $_POST['postcode'] );
        $city           =   sanitize_text_field( $_POST['city'] );
        $state          =   sanitize_text_field( $_POST['state'] );
        $country        =   sanitize_text_field( $_POST['country'] );
        $phone          =   sanitize_text_field( $_POST['phone'] );
        $email          =   sanitize_email( $_POST['email'] );
        $others         =   sanitize_text_field( $_POST['others'] );
        $arrivaldate    =   sanitize_text_field($_POST['arrivaldate']);
        $departuredate  =   sanitize_text_field($_POST['departuredate']);
        $regoption      =   sanitize_text_field($_POST['regoption']) ;
        $studentcheck   =   sanitize_text_field($_POST['studentcheck']);
        $extradinner    =   sanitize_text_field($_POST['extradinner']);
        $totalamount    =   $_POST['totaltopay'];
        $firsttime      =   sanitize_text_field($_POST['firsttime']);
        $needvisa       =   sanitize_text_field($_POST['needvisa']);
        $easychairid    =   sanitize_text_field($_POST['easychairid']);
        $paymentmethod  =   sanitize_text_field( $_POST['paymentmethod'] );
        $billingname    =   sanitize_text_field( $_POST['billingname'] );
        $billingvat     =   sanitize_text_field( $_POST['billingvat'] );
        $billingaddress =   sanitize_text_field( $_POST['billingaddress'] );
        $billingpostcode=   sanitize_text_field( $_POST['billingpostcode'] );
        $billingcity    =   sanitize_text_field( $_POST['billingcity'] );
        $billingstate   =   sanitize_text_field( $_POST['billingstate'] );
        $billingcountry =   sanitize_text_field( $_POST['billingcountry'] );
        
        $status             = "confirmationform";
    
        $registration_info = array(
                'firstname' => $firstname,
                'lastname' => $lastname,
                'affiliation' => $affiliation,
                'profile' => $profile,
                'address' => $address,
                'postcode' => $postcode,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'phone' => $phone,
                'email' => $email,
                'others' => $others,
                'arrivaldate' => $arrivaldate,
                'departuredate' =>  $departuredate,
                'regoption' =>$regoption,
                'studentcheck' =>$studentcheck,
                'totalamount' =>  $totalamount,
                'firsttime' =>    $firsttime,
                'needvisa' =>     $needvisa,
                'easychairid' => $easychairid,
                'extradinner'=>$extradinner,
                'totalamount' =>$totalamount,
                'paymentmethod' =>$paymentmethod,
                'billingname'  =>$billingname,
                'billingvat' =>$billingvat,
                'billingaddress' =>$billingaddress,
                'billingpostcode' =>$billingpostcode,
                'billingcity' =>$billingcity,
                'billingstate' =>$billingstate,
                'billingcountry' =>$billingcountry,
                'paymentdone' =>0
        );
        //We should wait until confirmation
        //$idRegistration = complete_registration($registration_info);
        //emailAdminRegistration($idRegistration);
    }else if (isset($_POST["confirm"])){
        $status = "confirmed";
		//$validate = validate_registration($_POST);
		//if ($validate){
        	$idRegistration = complete_registration($_POST);
			$registration_info = $_POST;
            
			if ($registration_info["paymentmethod"]=="methodtransfer"){
				emailAllTransfer($idRegistration);
			}
            sendMessageSlack($idRegistration);
            
		//}else $status = "error";
    }else {    
        if (isset($_GET["payment"])){
            $idRegistration = $_GET["idOrder"];
            $status = (($_GET["payment"]=="OK")?"paymentsuccess":"paymentfailure");
            payment_processing($idRegistration,$_GET["payment"]);
            if ($status=="paymentsuccess"){
                emailAdminRegistration($idRegistration);
                emailUserRegistration($idRegistration);
                emailPaymentSupervisorRegistration($idRegistration);
                //sendMessageSlack($idRegistration);
                
            }
        }
    }
    if (isset($_GET["prefill"]) && $status=='registrationform'){
        $registration_info = prefillinfo_test();
    }

    generate_view($registration_info, $status, $idRegistration, $student, $params);
}

function generate_view($registration_info, $status, $idRegistration, $student,$params) {
    if ($status == 'registrationform'){
        registration_form($registration_info, $student, $params);
    }else if ($status == 'confirmationform'){
        confirmation_form($registration_info);    
    }else if ($status == 'confirmed'){
        if ($registration_info["paymentmethod"] == "methodtpv"){
            prepayment_form($registration_info, $idRegistration);
        }else{
            transfer_info($registration_info,$idRegistration);
        }
    }else if($status == "paymentsuccess" || $status== "paymentfailure"){
        payment_confirmation($_GET["idOrder"],$_GET["payment"]);
    }else{
		echo "There was an error. Please try again or contact admin";
	}
}

function prefillinfo_test(){
    $registration_info = array(
        'firstname' => "Antonio Manuel",
        'lastname' => "Gutierrez",
        'affiliation' => "Universidad de Sevilla",
        'profile' => "Lecturer",
        'address' => "Avda. Reina Mercedes",
        'postcode' => "41001",
        'city' => "Sevilla",
        'state' => "Sevilla",
        'country' => "ES",
        'phone' => "+34655555555",
        'email' => "antoniomanuel@gmail.com",
        'others' => "",
        'regoption' => 4,
        'paymentmethod' =>"methodtpv"
        );
    //sendMessageSlack(18);
    return $registration_info;
}

function validate_registration($registration_info){
	$validate = false;
	
	
	if ($registration_info['firstname']!="" 
	&& $registration_info['lastname']!="" 
	&& $registration_info['email']!="" 
	&& $registration_info['totalamount']!="" 
	&& intval($registration_info['extradinner'])>=0){
		$recaptcha = validate_recaptcha($_POST["g-recaptcha-response"]);
		if ($recaptcha)
			$validate = true;
	}
	return $validate;
}

function validate_recaptcha($recaptcha){
	$validate_r = false;
	$response = $_POST["g-recaptcha-response"];
	$url = 'https://www.google.com/recaptcha/api/siteverify';
	$data = array(
		'secret' => '6LdSQSQUAAAAACeAlvSNGBzIxnSqMKbLcZTHVJKn',
		'response' => $response
	);
	$options = array(
		'http' => array (
			'method' => 'POST',
			'content' => http_build_query($data)
		)
	);
	$context  = stream_context_create($options);
	$verify = file_get_contents($url, false, $context);
	$captcha_success=json_decode($verify);
	if ($captcha_success->success==false) {
		$validate_r = false;
	} else if ($captcha_success->success==true) {
		$validate_r = true;
	}
	return $validate_r;
}

function processActionConfirm(){
    if (isset($_GET["confirm"]) && isset($_GET["idOrder"])){
        if ($_GET["confirm"]==1) {
            confirm_registration($_GET["idOrder"]);
            $aUser = get_registration_info($idRegistration);
            emailAdminRegistration($_REQUEST["idOrder"]);
            emailUserRegistration($_REQUEST["idOrder"]);
            emailPaymentSupervisorRegistration($_REQUEST["idOrder"]);
            
        }
        else unconfirm_registration($_GET["idOrder"]);
    }
}

?>