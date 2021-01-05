<?php

/*
Plugin Name: Contact Form 7 Freebie
Plugin URI: http://munyagu.com/contact-form-7-freebie/
Description: Add a little functionality to the Contact Form 7
Version: 1.1.5
Textdomain: contact-form-7-freebie
Author: munyagu
Author URI: http://munyagu.com/
License: GPL2
*/

include 'inc/ContactForm7Freebie.php';
include 'inc/ContactForm7ReCaptcha.php';

/* set WPCF7_AUTOP common setting */
$options = ContactForm7Freebie::get_options();
if ( $options !== null && is_array( $options ) ) {
	$potion_values = array_values( $options );
	$autop         = $potion_values[0][ ContactForm7Freebie::$f_no_wpautop_field_name ];
	if ( $autop === '1' ) {
		if ( ! defined( 'WPCF7_AUTOP' ) ) {
			define( 'WPCF7_AUTOP', false );
		}
	}
}
$partial_url = ContactForm7Freebie::get_instance();


ContactForm7ReCaptcha::init();
