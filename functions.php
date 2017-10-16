<?php
function splcregistration_menu() {

	add_menu_page(
		'splcregistration_settings',					// The value used to populate the browser's title bar when the menu page is active
		'SPLC Registration',					// The text of the menu in the administrator's sidebar
		'administrator',					// What roles are able to access the menu
		'splcregistration_settings',				// The ID used to bind submenu items to this menu 
		'splcregistration_settings_display'				// The callback function used to render this menu
	);

	add_submenu_page( 
		'splcregistration_settings', 
		'List Users', 
		'List Users', 
		'administrator', 
		'splcregistration_users', 
		'splcregistration_users_display');
	
} // end sandbox_example_theme_menu

add_action( 'admin_menu', 'splcregistration_menu' );
add_action('admin_init','customdownload');
function splcregistration_users_display( $active_tab = '' ) {
	processActionUsers();
	echo getTableHtmlAll();
}

function customdownload(){
	if (isset($_GET["action"]) && $_GET["action"]=="generatecsv"){
		downloadCSV();
	}else if (isset($_GET["action"]) && $_GET["action"]=="generateexcel"){
		downloadExcel();
	}else if (isset($_GET["action"]) && $_GET["action"]=="generatecountries"){
		generateExcelSummaryCountries();
	}else if (isset($_GET["action"]) && $_GET["action"]=="generateprivate"){
		generateExcelSummaryPrivate();
	}



}
/**
 * Renders a simple page to display for the theme menu defined above.
 */

function splcregistration_settings_display( $active_tab = '' ) {
?>
	<!-- Create a header in the default WordPress 'wrap' container -->
	<div class="wrap">
	
		<div id="icon-themes" class="icon32"></div>
		<h2><?php _e( 'SPLC Registration Options', 'splcregistration_settings' ); ?></h2>
		<?php settings_errors(); ?>
		
		
		<form method="post" action="options.php">
			<?php
				settings_fields( 'splcregistration_settings_input' );
				do_settings_sections( 'splcregistration_settings_input' );
				submit_button();
			?>
		</form>
	</div><!-- /.wrap -->
<?php
} // end sandbox_theme_display
/* ------------------------------------------------------------------------ *
 * Setting Registration
 * ------------------------------------------------------------------------ */ 

/**
 * Provides default values for the Input Options.
 */
function splcregistration_default_input_options() {
	
	$defaults = array(
		'urltpv_test'		=>	'',
		'urltpv_final'	=>	'',
		'merchantcode'	=>	'',
		'testmode'		=>	'',
		'paymentperiod'	=>	0,
		
		'time_options'		=>	'default'	
	);
	
	return apply_filters( 'splcregistration_default_input_options', $defaults );
	
} // end sandbox_theme_default_input_options
	
/**
 * Initializes the theme's input example by registering the Sections,
 * Fields, and Settings. This particular group of options is used to demonstration
 * validation and sanitization.
 *
 * This function is registered with the 'admin_init' hook.
 */ 
function splcregistration_initialize_input() {
	if( false == get_option( 'splcregistration_settings_input' ) ) {	
		add_option( 'splcregistration_input_examples', apply_filters( 'splcregistration_default_input_options', splcregistration_default_input_options() ) );
	} // end if
	add_settings_section(
		'splcregistration_section',
		__( 'SPLC Registration settings', 'sandbox' ),
		'splcregistration_settings_callback',
		'splcregistration_settings_input'
	);
	
	add_settings_field(	
		'URL TPV FINAL',						
		__( 'URL TPV FINAL', 'sandbox' ),							
		'urltpv_final_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);
	add_settings_field(	
		'URL TPV TEST',						
		__( 'URL TPV TEST', 'sandbox' ),							
		'urltpv_test_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);
	add_settings_field(	
		'MERCHANT CODE',						
		__( 'MERCHANT CODE', 'sandbox' ),							
		'merchantcode_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);

	add_settings_field(	
		'TEST MODE',						
		__( 'TEST MODE', 'sandbox' ),							
		'testmode_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);

	add_settings_field(	
		'CURRENT PAYMENT PERIOD',						
		__( 'CURRENT PAYMENT PERIOD', 'sandbox' ),							
		'paymentperiod_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);

	add_settings_field(	
		'CONTACT EMAIL',						
		__( 'CONTACT EMAIL', 'sandbox' ),							
		'contactemail_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);

	add_settings_field(	
		'KC FINAL',						
		__( 'KC FINAL', 'sandbox' ),							
		'kcchannels_final_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);
	add_settings_field(	
		'KC TEST',						
		__( 'KC TEST', 'sandbox' ),							
		'kcchannels_test_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);
	
	add_settings_field(	
		'SLACK CHANNEL',						
		__( 'SLACK CHANNEL', 'sandbox' ),							
		'slackchannel_callback',	
		'splcregistration_settings_input',	
		'splcregistration_section'			
	);
	
	
	register_setting(
		'splcregistration_settings_input',
		'splcregistration_settings_input',
		'splcregistration_settings_validate'
	);
} // end sandbox_theme_initialize_input_examples
add_action( 'admin_init', 'splcregistration_initialize_input' );
/* ------------------------------------------------------------------------ *
 * Section Callbacks
 * ------------------------------------------------------------------------ */ 
/**
 * This function provides a simple description for the Input Examples page.
 *
 * It's called from the 'sandbox_theme_initialize_input_examples_options' function by being passed as a parameter
 * in the add_settings_section function.
 */
function splcregistration_settings_callback() {
	echo '<p>' . __( 'Provides examples of the five basic element types.', 'sandbox' ) . '</p>';
} // end sandbox_general_options_callback
/* ------------------------------------------------------------------------ *
 * Field Callbacks
 * ------------------------------------------------------------------------ */ 

function kcchannels_final_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	
	// Render the output
	echo '<input type="text" size="80" id="urltpv_final" name="splcregistration_settings_input[kcchannels_final]" value="' . $options['kcchannels_final'] . '" />';
	
}

function kcchannels_test_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	
	// Render the output
	echo '<input type="text" size="80" id="urltpv_final" name="splcregistration_settings_input[kcchannels_test]" value="' . $options['kcchannels_test'] . '" />';
	
}

function urltpv_test_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	
	// Render the output
	echo '<input type="text" size="80" id="urltpv_test" name="splcregistration_settings_input[urltpv_test]" value="' . $options['urltpv_test'] . '" />';
	
}

function urltpv_final_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	
	// Render the output
	echo '<input type="text" size="80" id="urltpv_final" name="splcregistration_settings_input[urltpv_final]" value="' . $options['urltpv_final'] . '" />';
	
}

function slackchannel_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	
	// Render the output
	echo '<input type="text" size="80" id="urltpv_final" name="splcregistration_settings_input[slackchannel]" value="' . $options['slackchannel'] . '" />';
	
}

function testmode_callback() {
	$options = get_option( 'splcregistration_settings_input' );
	
	$html = '<input type="checkbox" id="testmode" name="splcregistration_settings_input[testmode]" value="1"' . checked( 1, $options['testmode'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="testmode">Test Mode</label>';
	
	echo $html;
}

function merchantcode_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	
	// Render the output
	echo '<input type="text" id="merchantcode" name="splcregistration_settings_input[merchantcode]" value="' . $options['merchantcode'] . '" />';
	
}

function paymentperiod_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	// Render the output
	echo '<input type="radio" id="paymentperiod" name="splcregistration_settings_input[paymentperiod]" '.($options['paymentperiod']==0?"checked ":"").'value="0" />Early Registration';
	echo '<input type="radio" id="paymentperiod" name="splcregistration_settings_input[paymentperiod]" '.($options['paymentperiod']==1?"checked ":"").'value="1" />Regular Registration';
	echo '<input type="radio" id="paymentperiod" name="splcregistration_settings_input[paymentperiod]" '.($options['paymentperiod']==2?"checked ":"").'value="2" />Late/On Site Registration';
	
}

function contactemail_callback() {
	
	$options = get_option( 'splcregistration_settings_input' );
	
	// Render the output
	echo '<input type="text" id="contactemail" name="splcregistration_settings_input[contactemail]" value="' . $options['contactemail'] . '" />';
	
}

/* ------------------------------------------------------------------------ *
 * Setting Callbacks
 * ------------------------------------------------------------------------ */ 

function splcregistration_settings_validate( $input ) {
	// Create our array for storing the validated options
	$output = array();
	
	// Loop through each of the incoming options
	foreach( $input as $key => $value ) {
		
		// Check to see if the current option has a value. If so, process it.
		if( isset( $input[$key] ) ) {
		
			// Strip all HTML and PHP tags and properly handle quoted strings
			$output[$key] = strip_tags( stripslashes( $input[ $key ] ) );
			
		} // end if
		
	} // end foreach
	
	// Return the array processing any additional functions filtered by this action
	return apply_filters( 'splcregistration_settings_validate', $output, $input );
} // end sandbox_theme_validate_input_examples
?>