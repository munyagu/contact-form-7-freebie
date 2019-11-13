<?php

class ContactForm7ReCaptcha {

	const pattern = '/form.+?id=\"wpcf7\-/';

	public static function init() {

		add_action( 'wp_head', array( __CLASS__, 'wp_head' ) );
		add_action( 'wp_footer', array( __CLASS__, 'wp_footer' ) );

	}


	/*
	public static function reCaptchaInit(){

		global $post;

		$show = false;

		if( is_singular( $post->post_type ) ) {

			$result = preg_match( '/\[contact\-form\-7 /', $post->post_content );

			if( 1 === $result ) {
				$show = true;
			}
		}

		if( false === $show ) {
			wp_dequeue_script( 'google-recaptcha' );
			remove_action( 'wp_footer', 'wpcf7_recaptcha_onload_script', 40, 0 );
		}

	}
	*/

	public static function wp_head() {

		$service = WPCF7_RECAPTCHA::get_instance();
		if ( $service->is_active() ) {
			ob_start( null, 0, PHP_OUTPUT_HANDLER_FLUSHABLE || PHP_OUTPUT_HANDLER_REMOVABLE || PHP_OUTPUT_HANDLER_CLEANABLE );
		}

	}

	/**
	 * Check content contains Contact Form 7 form.
	 */
	public static function wp_footer() {

		$service = WPCF7_RECAPTCHA::get_instance();

		if ( $service->is_active() ) {
			$recaptcha = get_option( ContactForm7Freebie::$f_field_remove_recaptcha_badge );

			if ( '1' === $recaptcha ) {

				$content = ob_get_contents();
				$result  = preg_match( self::pattern, $content );

				if ( 1 !== $result ) {
					wp_dequeue_script( 'google-recaptcha' );
					remove_action( 'wp_footer', 'wpcf7_recaptcha_onload_script', 40, 0 );

				}
				ob_flush();
			}
		}
	}

}
