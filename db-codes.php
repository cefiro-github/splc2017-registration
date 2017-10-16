<?php
$tablecode_name = $wpdb->prefix.'splcregistration_code';


function check_code($code) {
	global $wpdb,$tablecode_name;
	$code_info  = $wpdb->get_row("SELECT * FROM ".$tablecode_name." WHERE code = ".$code, ARRAY_A );
	if ($code_info==NULL){
		return 0;
	}else if ($code_info["used"]=="1"){
		return 2;
	}else return 1;
}

function check_code_email($code) {
	global $wpdb,$tablecode_name;
	$tablecode_name = $wpdb->prefix . 'splcregistration_code';
	$code_info  = $wpdb->get_row("SELECT * FROM ".$tablecode_name." WHERE email = ".$email, ARRAY_A );
	return $code_info;
}

function insert_code($email,$code) {
    global $reg_errors;
    global $wpdb,$tablecode_name;
	$tablecode_name = $wpdb->prefix . 'splcregistration_code';
	$wpdb->show_errors();
	$result = $wpdb->insert( 
		$table_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'email' => $email,
			'code' => $code,
			'used' => "0"		) 
	);
	$last_id = $wpdb->insert_id;
    return $last_id;   
}

function update_code($code,$idOrder){
	global $wpdb,$tablecode_name;
	$wpdb->update($tablecode_name,array('used'=> "1",'idOrder' =>"idOrder" ), array('code'=>$code));
}

?>