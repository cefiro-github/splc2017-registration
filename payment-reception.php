<?php

function payment_processing($idOrder, $code){
    if ($code=="OK"){
        update_payment($idOrder,true);
    }
}

function payment_confirmation($idOrder, $code){
    $contactemail = get_option('splcregistration_settings_input')["contactemail"];
    $message_success = "Your registration has been successful.<br/>You will receive shortly a confirmation email."; 
    $message_failure = "ERROR: Your payment has failed for some reason and your registration has NOT been successful. Please, go back to the registration page and repeat the process. If you have any problem, please, contact us: <a href='mailto:splc2017@us.es'>".$contactemail."</a>"; 
    if ($code=="OK"){
        $message = $message_success;
    }else $message = $message_failure;
    echo "<fieldset>";
    echo "<span>".$message."</span>";
    echo "</fieldset>";
}

?>