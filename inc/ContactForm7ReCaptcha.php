<?php

class ContactForm7ReCaptcha {

	const pattern = '/form.+?id=\"wpcf7\-/';

	public static function init() {

		add_action( 'wp_head', array( __CLASS__, 'wp_head' ) );
		add_action( 'wp_footer', array( __CLASS__, 'wp_footer' ) );

	}

	public static function wp_head() {


		if ( self::enable() ) {
			ob_start();
		}

	}


	/**
	 * Check content contains Contact Form 7 form.
	 */
	public static function wp_footer() {

		if ( self::enable() ) {

			//ob_start();
			$content = ob_get_contents();
			$result  = preg_match( self::pattern, $content );

			if ( 1 !== $result ) {
				wp_dequeue_script( 'wpcf7-recaptcha' );
				wp_dequeue_script( 'google-recaptcha' );

				remove_action( 'wp_footer', 'wpcf7_recaptcha_onload_script', 40, 0 ); // before Contact Form 7 5.1.9
			}

			ob_flush();
		}
	}

	public static function enable() {

		$service   = WPCF7_RECAPTCHA::get_instance();
		$recaptcha = get_option( ContactForm7Freebie::$f_field_remove_recaptcha_badge );

		return $service->is_active() && '1' === $recaptcha;
	}

}
