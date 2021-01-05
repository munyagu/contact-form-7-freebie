<?php

class ContactForm7ReCaptcha {

	const pattern = '/form.+?id=\"wpcf7\-/';

	public static function init() {

		add_action( 'wp_head', array( __CLASS__, 'wp_head' ) );
		add_action( 'wp_footer', array( __CLASS__, 'wp_footer' ) );

	}

	public static function wp_head() {
		?>
<style>.grecaptcha-hide{visibility: hidden !important;}</style>
		<?php
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
			    $script = <<<EOT
<script>
        var target = document.querySelector('body');
        var observer = new MutationObserver(function(mutations){
            mutations.forEach(function(mutation){
                if( mutation.addedNodes.length > 0 ) {
                    var logo = mutation.addedNodes[0].firstChild;
                    if( null !== logo && 'grecaptcha-badge' === logo.className ) {
                        logo.className = 'grecaptcha-badge grecaptcha-hide';
                        observer.disconnect();
                    }
                }
            });
        });
        observer.observe(target, { childList: true });
</script>
EOT;
                echo trim( $script );
			}

			ob_flush();
		}
	}

	public static function enable() {

		if( class_exists( 'WPCF7_RECAPTCHA' ) ) {
			$service   = WPCF7_RECAPTCHA::get_instance();
			$recaptcha = get_option( ContactForm7Freebie::$f_field_remove_recaptcha_badge );

			return $service->is_active() && '1' === $recaptcha;
		} else {
			return false;
		}

	}

}
