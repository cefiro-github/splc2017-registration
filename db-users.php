<?php

global $splcregistration_db_version;
$splcregistration_db_version = '1.0';

function splcregistration_install() {
	global $wpdb;
	global $splcregistration_db_version;

	$table_name = $wpdb->prefix . 'splcregistration';
	
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		paymenttime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		firstname text NOT NULL,
		lastname text NOT NULL,
		affiliation text NOT NULL,
		profile text NOT NULL,
		address text NOT NULL,
		postcode text NOT NULL,
		city text NOT NULL,
		state text NOT NULL,
		country text NOT NULL,
		phone text NOT NULL,
		email text NOT NULL,
		others text NOT NULL,
		arrivaldate mediumint(12) NOT NULL,
		departuredate mediumint(12) NOT NULL,
		regoption text NOT NULL,
		studentcheck text,
		extradinner text,
		firsttime text,
		needvisa text,
		easychairid text,
		totalamount mediumint(9) NOT NULL,
		paymentmethod text NOT NULL,
		billingname text NOT NULL,
		billingvat text NOT NULL,
		billingaddress text NOT NULL,
		billingpostcode text NOT NULL,
		billingcity text NOT NULL,
		billingstate text NOT NULL,
		billingcountry text NOT NULL,
		paymentdone text NOT NULL,
		trashed text NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	$tablecode_name = $wpdb->prefix . 'splcregistration_code';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE ".$tablecode_name." (
		idcode mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		email text NOT NULL,
		idOrder text NOT NULL,
		code text NOT NULL,
		used text NOT NULL,
		PRIMARY KEY  (idcode)
	) $charset_collate;";
	dbDelta( $sql );
	
	add_option( 'splcregistration_db_version', $splcregistration_db_version );
}




function complete_registration($registration_info) {
    global $reg_errors;
    global $wpdb;
    if ( 1 ) {
        //$user = wp_insert_user( $userdata );

		$table_name = $wpdb->prefix . 'splcregistration';
        $wpdb->show_errors();
		$result = $wpdb->insert( 
			$table_name, 
			array( 
				'time' => current_time( 'mysql' ), 
				'firstname' => $registration_info['firstname'],
				'lastname' => $registration_info['lastname'],
				'affiliation' => $registration_info['affiliation'],
                'profile' => $registration_info['profile'],
                'address' => $registration_info['address'],
                'postcode' => $registration_info['postcode'],
                'city' => $registration_info['city'],
                'state' => $registration_info['state'],
                'country' => $registration_info['country'],
                'phone' => $registration_info['phone'],
                'email' => $registration_info['email'],
                'others' => $registration_info['others'],
				'arrivaldate' => $registration_info['arrivaldate'],
				'departuredate' => $registration_info['departuredate'],
				'regoption' =>$registration_info['regoption'],
				'studentcheck' =>$registration_info['studentcheck'],
				'extradinner'=>$registration_info['extradinner'],
				'totalamount' =>$registration_info['totalamount'],
				'paymentmethod' =>$registration_info['paymentmethod'],
				'firsttime' => $registration_info['firsttime'],
				'needvisa' => $registration_info['needvisa'],
				
				'easychairid' =>$registration_info['easychairid'],
				'billingname'  =>$registration_info['billingname'],
				'billingvat' =>$registration_info['billingvat'],
				'billingaddress' =>$registration_info['billingaddress'],
				'billingpostcode' =>$registration_info['billingpostcode'],
				'billingcity' =>$registration_info['billingcity'],
				'billingstate' =>$registration_info['billingstate'],
				'billingcountry' =>$registration_info['billingcountry'],
				'paymentdone' => 0,
				'trashed' => '0'
			) 
		);
		$last_id = $wpdb->insert_id;
        //echo 'Result: '.$result;
        //$wpdb->print_error();
        return $last_id;   
    }
}

function get_registration_info($idRegistration){
	global $wpdb;
	$table_name = $wpdb->prefix . 'splcregistration';
	$registration_info  = $wpdb->get_row("SELECT * FROM ".$table_name." WHERE id = ".$idRegistration, ARRAY_A );
	return $registration_info;
}

function get_all_registrations($trashed, $paid, $transferpendant){
	global $wpdb;
	$table_name = $wpdb->prefix . 'splcregistration';
	$sql = "SELECT * FROM ".$table_name." WHERE ";
	if ($trashed)
		$sql .= " trashed=1 ";
	else 
		$sql .= " trashed<>1 ";
	if ($paid)
		$sql .= " AND paymentdone=1 ";
	if ($transferpendant)
		$sql .= " AND paymentmethod='methodtransfer' AND paymentdone=0 ";
	$registration_info  = $wpdb->get_results($sql, ARRAY_A );
	return $registration_info;
}

function confirm_registration($registration_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'splcregistration';
	$wpdb->update($table_name,array('paymentdone'=> 1,'paymenttime' => current_time( 'mysql' ), ), array('id'=>$registration_id));

}

function unconfirm_registration($registration_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'splcregistration';
	$wpdb->update($table_name,array('paymentdone'=> 0,'paymenttime' => current_time( 'mysql' ), ), array('id'=>$registration_id));

}

function trash_user($registration_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'splcregistration';
	$wpdb->update($table_name,array('trashed'=> "1"), array('id'=>$registration_id));
}

function untrash_user($registration_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'splcregistration';
	$wpdb->update($table_name,array('trashed'=> "0"), array('id'=>$registration_id));
}

function update_payment($registration_id, $result){
	global $wpdb;
	$table_name = $wpdb->prefix.'splcregistration';
	$wpdb->update($table_name,array('paymentdone'=> ($result?1:2),'paymenttime' => current_time( 'mysql' ), ), array('id'=>$registration_id));

}
