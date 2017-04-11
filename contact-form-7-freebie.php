<?php

/*
Plugin Name: Contact Form 7 Freebie
Plugin URI: http://munyagu.com/contact-form-7-freebie/
Description: Contact Form 7のちょっとした追加機能
Version: 1.0.0
Textdomain: contact-form-7-freebie
Author: munyagu
Author URI: http://munyagu.com/
License: GPL2
*/

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


class ContactForm7Freebie {

	private static $instance = null;

	private $name = '';
	private static $textdomain = 'contact-form-7-freebie';
	private $version = '1.0.0';

	private static $option_name = 'cf7-freebie';
	public $options = null;

	public static $f_no_wpautop_field_name = "cf7f_field_no_wpautop";
	private static $f_email_field_name = "cf7f_email";
	private static $f_confirm_field_name = "cf7f_email_confirm";
	private static $f_thanks_field_name = "cf7f_thanks_url";
	private static $f_multiple_newline = "cf7f_multiple_newline";
	private static $f_field_error_field_name = "cf7f_field_error";

	private $redirect_script = '';

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new ContactForm7Freebie();
		}

		return self::$instance;
	}

	private function __construct() {
		$plugin_meta   = get_file_data( __FILE__, array(
			'name'    => 'Plugin Name',
			'version' => 'Version',
		) );
		$this->name    = $plugin_meta['name'];
		$this->version = $plugin_meta['version'];

		load_plugin_textdomain( 'contact-form-7-freebie', false, 'contact-form-7-freebie/languages' );

		/* control load order of plugin */
		add_filter( 'pre_update_option_active_plugins', array( $this, 'pre_update_option_active_plugins' ), 99, 3 );

		/* show script for redirect thanks page */
		add_action( 'wpcf7_contact_form', array( $this, 'wpcf7_contact_form' ), 10, 1 );

		/* activate and deactivate plugin */
		register_activation_hook( __FILE__, array( 'ContactForm7Freebie', 'register_activation_hook' ) );
		register_uninstall_hook( __FILE__, array( 'ContactForm7Freebie', 'register_uninstall_hook' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		/* email validation */
		add_filter( 'wpcf7_validate_email', array( $this, 'wpcf7_validate_email' ), 10, 2 );
		add_filter( 'wpcf7_validate_email*', array( $this, 'wpcf7_validate_email' ), 10, 2 );

		/* add tab */
		if ( is_admin() ) {
			add_filter( 'wpcf7_editor_panels', array( $this, 'wpcf7_editor_panels' ), 10, 1 );
			add_action( 'wpcf7_save_contact_form', array( $this, 'wpcf7_save_contact_form' ), 10, 3 );
		}

		/* field error messages */
		add_action( 'wpcf7_enqueue_styles', array( $this, 'wpcf7_enqueue_styles' ) );

		/* Make the comma of multiple values a newline in mail  */
		add_filter( 'wpcf7_mail_tag_replaced', array( $this, 'wpcf7_mail_tag_replaced' ), 10, 3 );

		add_filter( 'wpcf7_ajax_json_echo', array( $this, 'wpcf7_ajax_json_echo' ), 10, 2 );
	}


	/**
	 * control load order of plugin(load first)
	 * @return array
	 */
	public function pre_update_option_active_plugins( $plugins, $old_value, $option ) {
		$this_plugin = str_replace( wp_normalize_path( WP_PLUGIN_DIR ) . '/', '', wp_normalize_path( __FILE__ ) );
		foreach ( $plugins as $num => $path ) {
			if ( $path == $this_plugin ) {
				unset( $plugins[ $num ] );
				array_unshift( $plugins, $this_plugin );
				break;
			}
		}

		return $plugins;
	}

	/**
	 * thanks redirect for earlier Contact Form 7 version 4.7
	 *
	 * @param $items
	 * @param $result
	 *
	 * @return mixed
	 */
	public function wpcf7_ajax_json_echo( $items, $result ) {
		// for earlier Contact Form 7 version 4.7
		if ( (int) WPCF7_VERSION >= 4.7 ) {
			return $items;
		}

		if ( isset( $items['onSentOk'] ) ) {
			foreach ( $items['onSentOk'] as $sent ) {
				if ( strpos( $sent, 'location=' ) !== false ) {
					// already contain location
					return $items;
				}
			}
		}

		$contact_form = WPCF7_ContactForm::get_current();
		if ( ! $contact_form ) {
			return $items;
		}

		$option = self::get_option( $contact_form->id() );
		if ( isset( $option[ self::$f_thanks_field_name ] ) && $option[ self::$f_thanks_field_name ] != '' ) {
			$items['onSentOk'][] = "location='{$option[self::$f_thanks_field_name]}'";
		}

		return $items;
	}

	/**
	 * thanks redirect for Contact Form 7 version 4.7 or later
	 *
	 * @param $contact_form
	 */
	public function wpcf7_contact_form( $contact_form ) {

		// for Contact Form 7 version 4.7 or later
		if ( (int) WPCF7_VERSION < 4.7 ) {
			return;
		}

		// show redirect script for thanks page.
		if ( ! is_admin() ) {
			$option = self::get_option( $contact_form->id() );
			if ( isset( $option[ self::$f_thanks_field_name ] ) && $option[ self::$f_thanks_field_name ] != '' ) {
				$this->redirect_script = <<<EOT
<script type='text/javascript' >
document.addEventListener( 'wpcf7mailsent', function( event ) {
    location = '{$option[self::$f_thanks_field_name]}';
}, false);
</script>
EOT;
				add_action( 'wp_footer', array( $this, 'wp_footer' ), 10 );
			}

		}
	}

	public function wp_footer() {

		if ( $this->redirect_script != '' ) {
			echo $this->redirect_script;
		}

	}

	/**
	 * validate email address matching
	 *
	 * @param $result
	 * @param $tag
	 *
	 * @return mixed
	 */
	public function wpcf7_validate_email( $result, $tag ) {

		global $cf7f_email_confirm;

		$id     = (int) $_POST['_wpcf7'];
		$option = self::get_option( $id );

		if ( isset( $option[ self::$f_email_field_name ] ) && $option[ self::$f_email_field_name ] != ''
		     && isset( $option[ self::$f_confirm_field_name ] ) && $option[ self::$f_confirm_field_name ]
		) {

			$tag = new WPCF7_FormTag( $tag );

			$name  = $tag->name;
			$value = isset( $_POST[ $name ] )
				? trim( wp_unslash( strtr( (string) $_POST[ $name ], "\n", " " ) ) )
				: '';

			if ( $name == $option[ self::$f_email_field_name ] ) {
				$cf7f_email_confirm = $value;
			}

			//$result->invalidate( $tag,__( $name . '/' . self::$confirm_field_name . ' ' . $cf7f_email_confirm . '/' . $value));


			if ( ( $name === $option[ self::$f_confirm_field_name ] ) && ( $cf7f_email_confirm !== $value ) ) {
				$result->invalidate( $tag, __( 'The confirmation email address does not match.', 'contact-form-7-freebie' ) );
			}
		}

		return $result;
	}

	/** Make the comma of multiple values a newline in mail */
	public function wpcf7_mail_tag_replaced( $replaced, $submitted, $html ) {

		$post   = wpcf7_get_current_contact_form();
		$option = self::get_option( $post->id() );

		if ( $option[ self::$f_multiple_newline ] === '1' && is_array( $submitted ) ) {
			$replaced = implode( "\n", $submitted );
		}

		return $replaced;
	}

	public function wpcf7_enqueue_styles() {

		if ( get_option( self::$f_field_error_field_name ) === '1' ) {
			wp_enqueue_style( self::$textdomain, plugin_dir_url( __FILE__ ) . 'include/css/contact-form-7-freebie.css', array( 'contact-form-7' ), $this->version );
		}
	}

	function wpcf7_editor_panels( $panels ) {
		$panels['cf7f-panel'] = array(
			'title'    => __( 'Freebie', 'contact-form-7-freebie' ),
			'callback' => array( $this, 'cf7f_panel' )
		);

		return $panels;
	}

	function cf7f_panel( $post ) {

		$option = self::get_option( $post->id() );


		?>
        <p><label><input type="checkbox" name="<?php echo self::$f_no_wpautop_field_name ?>" size="60"
                         value="1"<?php echo $option[ self::$f_no_wpautop_field_name ] == '1' ? 'checked="checked"' : ''; ?>><?php echo __( 'Do not insert P tag when displaying form', 'contact-form-7-freebie' ) ?>
            </label><br>
            <small>
                ※<?php echo __( 'If this function does not become effective, please invalidate Contact Form 7 Freebie plug-in and activate it.', 'contact-form-7-freebie' ) ?></small>
        </p>

        <p><label><?php echo __( 'Email Address Field Name', 'contact-form-7-freebie' ) ?><br><input type="text"
                                                                                                     name="<?php echo self::$f_email_field_name ?>"
                                                                                                     size="60"
                                                                                                     value="<?php echo $option[ self::$f_email_field_name ]; ?>"></label>
        </p>
        <p><label><?php echo __( 'Confirm Field Name', 'contact-form-7-freebie' ) ?><br><input type="text"
                                                                                               name="<?php echo self::$f_confirm_field_name; ?>"
                                                                                               size="60"
                                                                                               value="<?php echo $option[ self::$f_confirm_field_name ]; ?>"></label>
        </p>
        <p><label><?php echo __( 'Thanks Page URL', 'contact-form-7-freebie' ) ?><br><input type="text"
                                                                                            name="<?php echo self::$f_thanks_field_name; ?>"
                                                                                            size="60"
                                                                                            value="<?php echo $option[ self::$f_thanks_field_name ]; ?>"></label>
        </p>

        <p><label><input type="checkbox" name="<?php echo self::$f_multiple_newline; ?>" size="60"
                         value="1"<?php echo $option[ self::$f_multiple_newline ] == '1' ? 'checked="checked"' : ''; ?>>
				<?php echo __( 'Make the comma of multiple values a newline in mail body', 'contact-form-7-freebie' ) ?>
            </label></p>

        <p><label>
                <input type="checkbox" name="<?php echo self::$f_field_error_field_name; ?>" size="60"
                       value="1"<?php echo $option[ self::$f_field_error_field_name ] == '1' ? 'checked="checked"' : ''; ?>>
				<?php echo __( 'Hide field error message', 'contact-form-7-freebie' ) ?>
            </label></p>
		<?php
	}

	function wpcf7_save_contact_form( $contact_form, $args, $context ) {

		$option = array(
			$args['id'] => array(
				self::$f_no_wpautop_field_name  => isset( $args[ self::$f_no_wpautop_field_name ] ) ? $args[ self::$f_no_wpautop_field_name ] : '',
				self::$f_email_field_name       => $args[ self::$f_email_field_name ],
				self::$f_confirm_field_name     => $args[ self::$f_confirm_field_name ],
				self::$f_thanks_field_name      => $args[ self::$f_thanks_field_name ],
				self::$f_multiple_newline       => $args[ self::$f_multiple_newline ],
				self::$f_field_error_field_name => isset( $args[ self::$f_field_error_field_name ] ) ? $args[ self::$f_field_error_field_name ] : '',
			)
		);

		self::update_option( $option );

	}

	public static function get_option( $id = null ) {
		// option default value
		$default = array(
			self::$f_no_wpautop_field_name  => '',
			self::$f_email_field_name       => '',
			self::$f_confirm_field_name     => '',
			self::$f_thanks_field_name      => '',
			self::$f_multiple_newline       => '',
			self::$f_field_error_field_name => ''
		);


		$option  = array();
		$options = self::get_options();

		if ( isset( $options[ $id ] ) ) {
			$option = wp_parse_args( $options[ $id ], $default );
		} else {
			$option = $default;
		}

		return $option;
	}

	public static function get_options() {

		$instance = self::get_instance();

		if ( $instance->options === null ) {
			$instance->options = get_option( self::$option_name );
		}

		return $instance->options;
	}

	/**
	 * update plugin setting
	 *
	 * @param $option
	 */
	public static function update_option( $option ) {
		global $wpdb;
		$instance = self::get_instance();

		$option = $wpdb->_escape( $option );

		$options = self::get_options();
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		foreach ( $option as $k => $v ) {
			$options[ $k ] = $v;
		}

		/*
		 * for set WPCF7_AUTOP false all settings
		 * one autop setting will be common setting
		 */
		$e             = new WP_Error();
		$potion_values = array_values( $option );
		$autop         = $potion_values[0][ self::$f_no_wpautop_field_name ];
		foreach ( $options as $key => $renew_potion ) {
			$options[ $key ][ self::$f_no_wpautop_field_name ] = $autop;
		}

		$instance->options = $options;
		update_option( self::$option_name, $instance->options );
	}


	/**
	 * activation hook
	 */
	public static function register_activation_hook() {
		if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			$e = new WP_Error( 'error', 'ERROR:000 ' . __( 'Please activate Contact Form 7 plugin in advance', 'contact-form-7-freebie' ) );
			set_transient( self::$textdomain . '-admin-errors', $e, 5 );
		}
	}

	public static function register_uninstall_hook() {
		delete_option( self::$option_name );
	}

	/**
	 * show error message
	 */
	public function admin_notices() {
		if ( $e = get_transient( self::$textdomain . '-admin-errors' ) ) {
			$messages = $e->get_error_messages();
			echo '<div class="error"';
			foreach ( $messages as $message ) {
				echo '<li>' . esc_html( $message ) . '</li>';
			}
			echo '</div>';
		}
	}

	/**
	 * deactivate plugin on error
	 */
	public function admin_init() {
		$e = get_transient( self::$textdomain . '-admin-errors' );
		if ( $e ) {
			$message = implode( $e->get_error_messages() );
			if ( strpos( $message, 'ERROR:000' ) !== false ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				unset( $_GET['activate'] );
			}
		}
	}
}

$partial_url = ContactForm7Freebie::get_instance();
