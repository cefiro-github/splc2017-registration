<?php
function transfer_info($registrationinfo,$idRegistration){
	$sHtml = "";  
  	$sHtml .="<fieldset><legend>Transfer info</legend>";


	$sHtml .= "<p>Your information is stored but your registration is not considered valid until the transfer has been done.  Please, proceed with the bank transfer as soon as possible using the following data:</p>";
 	$sHtml .= "<p>Total Amount:".$registrationinfo["totalamount"]." €";
 	$sHtml .= "<p><b>National Spanish Transfer:</b></p>";
    $sHtml .= "<p>VIAJES EL CORTE INGLÉS, S.A.</p>";
	$sHtml .= "<br/><p>BANCO SANTANDER - PLAZA DE CANALEJAS, 1 - 28014 Madrid Número de cuenta: ES37 0049 1500 0328 1035 5229</p>"; 
	$sHtml .= "<p><b>International:</b></p>"; 
	$sHtml .= "<br/><p>BENEFICIARY: VIAJES EL CORTE INGLÉS, S.A.</p>";
	$sHtml .= "<p>NAME OF ACCOUNT: BANCO BILBAO VIZCAYA ARGENTARIA</p>"; 
	$sHtml .= "<p>ADDRESS OF BANK: Oficina Corporativa c/ Alcalá 16 - 28014 Madrid</p>";
	$sHtml .= "<p>BIC (Swift number): BBVAESMMXXX</p>";
	$sHtml .= "<p>IBAN: ES97 0182 3999 3702 0066 4662<p>";
	$sHtml .= "<p>Please, indicate in the bank transfer concept: 'SPLC 2017 - ".$idRegistration." - ".$registrationinfo["firstname"]." ".$registrationinfo["lastname"]."'. Any bank charges must be paid by the sender. This guarantees that we will receive your payment in full</p>";
	$sHtml .= "<p>You should shortly receive an email with this information.</p>";
  	$sHtml .="</fieldset>";
  	echo $sHtml;
}



function prepayment_form($registration_info, $idRegistration){
	global $registrationpage;
    $jQuerySubmit = '
    <script>$(document).ready(function() {
        document.paymentdata.submit();
    });
    </script>';
    include 'apiRedsys.php';
        // Se crea Objeto
    $miObj          = new RedsysAPI;
        
    // Valores de entrada    
    $fuc            = get_option('splcregistration_settings_input')["merchantcode"];
    $isTest         = get_option('splcregistration_settings_input')["testmode"];
    if ($isTest)
        $url_tpv    = get_option('splcregistration_settings_input')["urltpv_test"];
    else 
        $url_tpv    = get_option('splcregistration_settings_input')["urltpv_final"];
    $terminal       = "001";
    $moneda         = "978";
    $trans          = "0";
    $idOrder        = str_pad($idRegistration,6,"0",STR_PAD_LEFT);//To be sure it has 4 characters
    $amount         = $registration_info["totalamount"]*100;

    //$name = 'Grupo ISA';
    $urlMerchant    = 'http://congreso.us.es/splc2017/'.$registrationpage;; // URL DEL COMERCIO
    $urlweb_ok      = $urlMerchant."?idOrder=".$idRegistration."&payment=OK"; // URL OK
    $urlweb_ko      = $urlMerchant."?idOrder=".$idRegistration."&payment=FAIL";; // URL NOK
    $concepto       = 'SPLC Registration';

    // Se Rellenan los campos
    $miObj->setParameter("DS_MERCHANT_URLOK",$urlweb_ok);      
    $miObj->setParameter("DS_MERCHANT_URLKO",$urlweb_ko);                
    $miObj->setParameter("DS_MERCHANT_AMOUNT",$amount);
    $miObj->setParameter("DS_MERCHANT_ORDER",strval($idOrder));
    $miObj->setParameter("DS_MERCHANT_MERCHANTCODE",$fuc);
    $miObj->setParameter("DS_MERCHANT_CURRENCY",$moneda);
    $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE",$trans);
    $miObj->setParameter("DS_MERCHANT_TERMINAL",$terminal);
    $miObj->setParameter("DS_MERCHANT_MERCHANTURL",$urlMerchant);
    
    
    //Datos de configuración
    $version="HMAC_SHA256_V1";

    $kc = get_option('splcregistration_settings_input')["kcchannels_test"]; 
    $kcprod = get_option('splcregistration_settings_input')["kcchannels_final"]; 
   
    $request = "";
    $params = $miObj->createMerchantParameters();
    $signature = $miObj->createMerchantSignature($kcprod);
    echo $jQuerySubmit.'
    <form name="paymentdata" id="paymentdata" action="' . $url_tpv . '" method="post">
    <input type="hidden" name="Ds_SignatureVersion" value="'. $version.'"> 
    <input type="hidden" name="Ds_MerchantParameters" value="'.$params.'"> 
    <input type="hidden" name="Ds_Signature" value="'.$signature.'">';
    echo "</form>";
}
?>