<?php

function confirmation_form($registration_info){
	
	wp_enqueue_script( 'scriptcaptcha','https://www.google.com/recaptcha/api.js', array( 'jquery' ), '1.0.0', true );
    $sHtml = getTableHtml($registration_info);
    $sHtml .= "<br/>";
    if ($registration_info["paymentmethod"]=="methodtpv"){
        $sHtml .= "NOTE: After the payment is accepted by the payment platform, be sure to click 'Continue' so the registration if fully processed.";
    }
    $sHtml .= "<form method='POST' id='hiddendataform' action='".$_SERVER["REQUEST_URI"]."'>";
    foreach ($registration_info as $key => $value){
        $sHtml .= "<input type='hidden' name='".$key."' value='".$value."'/>";
    }
    $sHtml .= "<input type='hidden' name='status' value='confirmed'/>";
	$sHtml .= "<div class=\"g-recaptcha\" data-sitekey=\"6LdSQSQUAAAAALqIyANpXctlH2u7dTz9FpnEDoF8\"></div>";
    $sHtml .= "<button name=\"confirm\" type=\"confirm\">Confirm</button>";
    $sHtml .="</form>";

    echo $sHtml;
}


?>