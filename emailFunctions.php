<?php

require 'classes/PHPMailerAutoload.php';

  	//1. USER: CONFIRMATION PAYMENT/REGISTRATION EMAIL;
    //2. USER: CONFIRMATION REGISTRATION WAITING TRANSFER;
    //3. ADMIN: CONFIRMATION REGISTRATION WAITING TRANSFER;
    //4. ADMIN: CONFIRMATION REGISTRATION AND TPV PAYMENT;
    //5. PAYMENT: CONFIRMATION REGISTRATION WAITING TRANSFER;

function emailAllTransfer($idOrder){
	emailUserTransfer($idOrder);
	emailAdminTransfer($idOrder);
	emailPaymentSupervisorTransfer($idOrder);	
}

function emailUserRegistration($idOrder){
	$subject = "[SPLC2017] Registration successful";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body = "Dear Mr./Mrs. ".htmlentities($aRegistrationInfo["lastname"]).", your registration is confirmed.";
	$body .= "These are your registration data: ";
	$body .= getTableHtml($idOrder);	
	sendEmail($aRegistrationInfo["email"], $subject,$body);
}

function emailUserTransfer($idOrder){
	$subject = "[SPLC2017] Transfer Pending";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body = htmlentities("Dear Mr./Mrs. ".$aRegistrationInfo["lastname"].", your registration won't be finished until we receive your transfer.");
	$body .= "These are your registration data: ";
	$body .= getTableHtml($idOrder);	
	$body .= "<br/>Please, indicate in the bank transfer concept: 'SPLC 2017 - ".$idOrder." - ".htmlentities($aRegistrationInfo["firstname"]." ".$aRegistrationInfo["lastname"])."'. ";
	$body .= "These are the data for the bank transfer:<br/><br/>";
	$body .= "<fieldset><p>Payment: ".$aRegistrationInfo["totalamount"]." euros</strong></p>
			<p><strong>National Spanish Transfer:</strong></p>
            <p>VIAJES EL CORTE INGLES, S.A.</p>
            <p>BANCO SANTANDER - PLAZA DE CANALEJAS, 1 - 28014 Madrid</p>
            <p>Numero de cuenta: ES37 0049 1500 0328 1035 5229</p>
            <p><strong>International:</strong></p>
            <p>BENEFICIARY: VIAJES EL CORTE INGLES, S.A.</p>
            <p>NAME OF ACCOUNT: BANCO BILBAO VIZCAYA ARGENTARIA</p>
            <p>ADDRESS OF BANK: Oficina Corporativa c/ Alcala 16 - 28014 Madrid</p>
            <p>BIC (Swift number): BBVAESMMXXX</p>
            <p>IBAN: ES97 0182 3999 3702 0066 4662</p></fieldset>";
	sendEmail($aRegistrationInfo["email"], $subject, $body);
}

function emailAdminTransfer($idOrder){
	global $emailsAdmin;
	$subject = "[SPLC2017] Transfer Pending";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body = "Dear Admin, an user has registered and is pendant of transfer. These are the registration data:";
	$body .= getTableHtml($idOrder);	
	sendEmail($emailsAdmin["localorganizer"], $subject, $body);
}

function emailAdminRegistration($idOrder){
	global $emailsAdmin;
	$subject = "[SPLC2017] User registered";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body = "Dear Admin, an user has registered and paid. These are the registration data:";
	$body .= getTableHtml($idOrder);	
	sendEmail($emailsAdmin["localorganizer"], $subject, $body);
}

function emailPaymentSupervisorRegistration($idOrder){
	global $emailsAdmin;
	$subject = "[SPLC2017] User registered";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body = "An user has registered and paid. These are the registration data:";
	$body .= getTableHtml($idOrder);	
	sendEmail($emailsAdmin["paymentsupervisor"], $subject, $body);
}

function emailPaymentSupervisorTransfer($idOrder){
	global $emailsAdmin;
	$subject = "[SPLC2017] Transfer Pending";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body = "An user has registered and is pendant of transfer. These are the registration data:";
	$body .= getTableHtml($idOrder);	
	sendEmail($emailsAdmin["paymentsupervisor"], $subject, $body);
}
    
/**    
function emailRegistrationComplete($idOrder){
	$subject = "Registration Complete";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body = "Dear Fulanito, your payment has been received so your registation is fullfilled";
	$body .= "These are your registration data ";
	$body .= getTableHtml($idOrder);	
	sendEmail($aRegistrationInfo["email"], $subject, $body);
}

function emailWaitingTransfer($idOrder){
	$subject = "Registration almost Complete";
	$body = "Dear Fulanito, your registration won't be finished until you make the transfer";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body .= "These are your registration data ";
	$body .= getTableHtml($idOrder);	
	$body .= "And these are the transfer data";
	sendEmail($aRegistrationInfo["email"], $subject, $body);
}

function emailTransferValidated($idOrder){
	$subject = "Registration Complete";
	$body = "Dear Fulanito, your transfer has been receibed so you are registered";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body .= "These are your registration data ";
	$body .= getTableHtml($idOrder);	
	sendEmail($aRegistrationInfo["email"], $subject, $body);
}

function emailStudentBeforeValidation($idOrder){
	$subject = "Wait until student validation";
	$body = "Dear Fulanito, your student card has to be verified to finish your registration";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body .= "These are your registration data ";
	$body .= getTableHtml($idOrder);	
	sendEmail($aRegistrationInfo["email"], $subject, $body);
}

function emailStudentValidatedWaitingTransfer($idOrder){
	$subject = "Wait until student validation";
	$body = "Dear Fulanito, your student card is valid";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body .= "These are your registration data ";
	$body .= getTableHtml($idOrder);	
	$body .= "And these are the transfer data";
	sendEmail($aRegistrationInfo["email"], $subject, $body);
}

function emailStudentValidatedPaymentTPV($idOrder){
	$subject = "Wait until student validation";
	$body = "Dear Fulanito, your student card is valid";
	$aRegistrationInfo = get_registration_info($idOrder);
	$body .= "These are your registration data ";
	$body .= getTableHtml($idOrder);	
	$body .= "And these are the link for payment";
	sendEmail($aRegistrationInfo["email"], $subject, $body);$body .= "And these is the link for payment";
}

function emailAdminRegistration($idOrder){
	global $emailsAdmin;
	$subject = "User registered in the system";
	$body = "Dear Admin, An user has registered";
	$body .= "<br/>These are his/her registration data ";
	$body .= getTableHtml($idOrder);	
	sendEmail($emailsAdmin["localorganizer"], $subject, $body);
}

function emailAdminPayment($idOrder){
	$subject = "User paid with the TPV";
	$body = "Dear Admin, A registered user has paid";
	$body .= "These are his/her registration data ";
	$body .= getTableHtml($idOrder);	
	sendEmail($emailsAdmin["localorganizer"], $subject, $body);
}

*/
function sendEmail($recipient, $subject, $body){
	$mail = new PHPMailer;

	//$mail->SMTPDebug = 4;                               // Enable verbose debug output

	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'mail.us.es';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = 'splc2017@us.es';                 // SMTP username
	$mail->Password = 'contactSPLC.2017';                           // SMTP password
	$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 587;                                    // TCP port to connect to
	/*$mail->SMTPOptions = array (
        'ssl' => array(
            'verify_peer'  => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true));*/
	$mail->setFrom('splc2017@us.es', 'SPLC2017 Registration');
	$mail->addAddress($recipient);     // Add a recipient
	
	$mail->isHTML(true);                                  // Set email format to HTML

	$mail->Subject = $subject;
	//$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
	$mail->Body    = $body;
	$mail->AltBody = $body;

	if(!$mail->send()) {
	//    echo 'Message could not be sent.';
	//   echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
	 //   echo 'Message has been sent';
	}
}