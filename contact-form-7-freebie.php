<?php

/*
Plugin Name: Contact Form 7 Freebie
Plugin URI: http://munyagu.com/
Description: Contact Form 7のちょっとした追加機能
Version: 1.0.0
Textdomain: contact-form-7-freebie
Author: munyagu
Author URI: http://munyagu.com/
License: GPL2
*/

class ContactForm7Freebie {

	private static $instance = null;

	private $name = '';
	private $textdomain = '';
	private $version = '1.0.0';

	private static $opstion_name = 'cf7f';
	public $options = null;

	private static $no_wpautop_field_name = "cf7f_field_no_wpautop";
	private static $email_field_name = "cf7f_email";
	private static $confirm_field_name = "cf7f_email_confirm";
	private static $thanks_field_name = "cf7f_thanks_url";
	private static $field_error_field_name = "cf7f_field_error";

	private $e = null;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new ContactForm7Freebie();
		}

		return self::$instance;
	}

	private function __construct() {
		$plugin_meta      = get_file_data( __FILE__, array(
			'name'       => 'Plugin Name',
			'version'    => 'Version',
			'textdomain' => 'Textdomain',
		) );
		$this->name       = $plugin_meta['name'];
		$this->textdomain = $plugin_meta['textdomain'];
		$this->version    = $plugin_meta['version'];

		load_plugin_textdomain( 'contact-form-7-freebie',false, 'contact-form-7-freebie/languages' );

		/* disnable Contact form 7 wpautop (Implement if filter is added to Contact form 7)*/
		/*add_filter('wpcf7_contact_form', array($this, 'wpcf7_form_elements'), 10, 1);*/

		/* activate and deactivate plugin */
		register_activation_hook( __FILE__, array( $this, 'register_activation_hook' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		/* email validation */
		add_filter( 'wpcf7_validate_email', array( $this, 'wpcf7_validate_email'), 10, 2 );
		add_filter( 'wpcf7_validate_email*', array( $this, 'wpcf7_validate_email'), 10, 2 );

		/* thanks redirect */
		add_action( 'wpcf7_mail_sent', array( $this, 'wpcf7_mail_sent' ), 1, 1 );

		/* add tab */
		add_filter('wpcf7_editor_panels', array($this, 'wpcf7_editor_panels'), 10, 1);
		add_action('wpcf7_save_contact_form', array($this, 'wpcf7_save_contact_form'), 10, 3);

		/* field error messages */
		add_action( 'wpcf7_enqueue_styles' , array( $this, 'wpcf7_enqueue_styles' ) );

		/* admin page */
		//add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/*Implement if filter is added to Contact form 7)*/
	/**
     * disable AUTOP
	 * @param $form
	 */
	/*
	public function wpcf7_form_elements($form) {
	    var_dump("wpcf7_contact_form");
	    if($this->get_option(self::$no_wpautop_field_name) === '1'){
		    if ( ! defined( 'WPCF7_AUTOP' ) ) {
			    define( 'WPCF7_AUTOP', false );
		    }
        }
        return $form;
    }
	*/

	/**
     * validate email address matching
	 * @param $result
	 * @param $tag
	 *
	 * @return mixed
	 */
	public function wpcf7_validate_email($result, $tag){

		global $cf7f_email_confirm;

        $id = (int)$_POST['_wpcf7'];
		$option = self::get_option($id);

		if(isset($option[self::$email_field_name]) && $option[self::$email_field_name] != ''
           && isset($option[self::$confirm_field_name]) && $option[self::$confirm_field_name]){

			$tag = new WPCF7_FormTag( $tag );

			$name = $tag->name;
			$value = isset( $_POST[$name] )
				? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
				: '';

			if ($name == $option[self::$email_field_name]){
				$cf7f_email_confirm = $value;
			}

			//$result->invalidate( $tag,__( $name . '/' . self::$confirm_field_name . ' ' . $cf7f_email_confirm . '/' . $value));


			if (($name === $option[self::$confirm_field_name]) && ($cf7f_email_confirm !== $value)){
				$result->invalidate( $tag,__('The confirmation email address does not match.', 'contact-form-7-freebie'));
			}
        }

	    return $result;
    }

	/**
     * redirect on mail sent
	 * @param $contact_form
	 */
	public function wpcf7_mail_sent( $contact_form ) {

	    $option = self::get_option($contact_form->id());
	    if(isset($option[self::$thanks_field_name]) && $option[self::$thanks_field_name] != ''){
		    wp_safe_redirect(  $option[self::$thanks_field_name] );
		    exit;
        }

	}

	public function wpcf7_enqueue_styles(){

        if(get_option( self::$field_error_field_name ) === '1'){
	        wp_enqueue_style( $this->textdomain, plugin_dir_url(__FILE__)  . 'include/css/contact-form-7-freebie.css', array('contact-form-7'), $this->version);
        }
    }

    function wpcf7_editor_panels($panels){
	    $panels['cf7f-panel'] = array(
		    'title' => __('Freebie', 'contact-form-7-freebie'),
		    'callback' => array($this, 'cf7f_panel') );
	    return $panels;
    }

    function cf7f_panel ($post){

        $option = self::get_option($post->id());

        //_dump($post);
?>
        <!-- <p><label><?php echo __('Do not insert P tag when displaying form', 'contact-form-7-freebie') ?> : <input type="checkbox" name="<?php echo self::$no_wpautop_field_name ?>" size="60"
                                                                                                  value="1"<?php echo $option[self::$no_wpautop_field_name] == '1' ? 'checked="checked"' : ''; ?>/></label></p> -->

        <p><label><?php echo __('Email Address Field Name', 'contact-form-7-freebie') ?><br/><input type="text" name="<?php echo self::$email_field_name ?>"
                                                                        size="60"
                                                                        value="<?php echo $option[self::$email_field_name]; ?>"/></label></p>
        <p><label><?php echo __('Confirm Field Name', 'contact-form-7-freebie') ?><br/><input type="text" name="<?php echo self::$confirm_field_name; ?>"
                                                                  size="60"
                                                                  value="<?php echo $option[self::$confirm_field_name]; ?>"/></label></p>
        <p><label><?php echo __('Thanks Page URL', 'contact-form-7-freebie') ?><br/><input type="text" name="<?php echo self::$thanks_field_name; ?>" size="60"
                                                               value="<?php echo $option[self::$thanks_field_name]; ?>"/></label></p>
        <p><label><?php echo __('Hide field error message', 'contact-form-7-freebie') ?> : <input type="checkbox" name="<?php echo self::$field_error_field_name; ?>" size="60"
                                                                        value="1"<?php echo $option[self::$field_error_field_name] == '1' ? 'checked="checked"' : ''; ?>/></label></p>
<?php
    }

    function wpcf7_save_contact_form($contact_form, $args, $context) {

        $option =  array($args['id'] => array(
	        self::$no_wpautop_field_name => isset($args[self::$no_wpautop_field_name]) ? $args[self::$no_wpautop_field_name] : '',
            self::$email_field_name => $args[self::$email_field_name],
            self::$confirm_field_name => $args[self::$confirm_field_name],
            self::$thanks_field_name => $args[self::$thanks_field_name],
            self::$field_error_field_name => isset($args[self::$field_error_field_name]) ? $args[self::$field_error_field_name] : '',
        ));

        self::update_option($option);

    }

    public static function get_option($id = null){
        $option = array( $id => array(
                'cf7f_email' => '',
                'cf7f_email_confirm' => '',
                'cf7f_thanks_url' => '',
                'cf7f_field_error' => ''
        )); // sample of option layout


        $option = array();
	    $options = self::get_options();

	    if(isset($options[$id])){
		    $option = $options[$id];
        }
        return $option;
    }

    public static function get_options(){

        $instance = self::get_instance();

        if($instance->options === null){
            $instance->options = get_option(self::$opstion_name);
        }
        return $instance->options;
    }

	public static function update_option($option){
        global $wpdb;
		$instance = self::get_instance();

		$option = $wpdb->_escape($option);

		$options = self::get_options();
		if(!is_array($options)) {
			$options = array();
		}

        foreach($option as $k => $v){
            $options[$k] = $v;
        }

		$instance->options = $options;
        update_option(self::$opstion_name, $instance->options);
	}


	/**
	 * add admin menu
	 */
	/*
	public function admin_menu() {
		add_submenu_page( 'wpcf7', __('Contact Form 7 Freebie'), __('Contact Form 7 Freebie'), 'manage_options', $this->textdomain, array(
			$this,
			'admin_page'
		) );
	}
	*/

	/**
	 * show admin page for this plugin
	 */
	/*
	public function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		if ( isset( $_POST['__nonce'] ) && $_POST['__nonce'] != '' ) {

			if ( ! wp_verify_nonce( $_POST['__nonce'], $this->textdomain ) ) {
				echo "Invalid Post.";
			} else {
				$email_value   = htmlspecialchars( $_POST[ self::$email_field_name ] );
				$confirm_value = htmlspecialchars( $_POST[ self::$email_field_name ] );
				$thanks_value  = htmlspecialchars( $_POST[ self::$thanks_field_name ] );
				$field_error_value  = htmlspecialchars( $_POST[ self::$field_error_field_name ] );

				update_option( self::$email_field_name, $email_value );
				update_option( self::$confirm_field_name, $confirm_value );
				update_option( self::$thanks_field_name, $thanks_value );
				update_option( self::$field_error_field_name, $field_error_value );

				echo "<p><strong>Updated</strong></p>";
			}
		} else {
			$email_value   = htmlspecialchars( get_option( self::$email_field_name ) );
			$confirm_value = htmlspecialchars( get_option( self::$confirm_field_name ) );
			$thanks_value  = htmlspecialchars( get_option( self::$thanks_field_name ) );
			$field_error_value  = get_option( self::$field_error_field_name );

		}
		?>
        <div class="wrap">
            <h2>Contact Form 7 Freebie</h2>
            <form name="form1" method="post" action="">
                <p><label><?php echo __('Email Address Field Name') ?> : <input type="text" name="<?php echo self::$email_field_name ?>"
                                                          size="60"
                                                          value="<?php echo $email_value ?>"/></label></p>
                <p><label><?php echo __('Confirm Field Name') ?> : <input type="text" name="<?php echo self::$confirm_field_name; ?>"
                                                    size="60"
                                                    value="<?php echo $confirm_value ?>"/></label></p>
                <p><label><?php echo __('Thanks Page URL') ?> : <input type="text" name="<?php echo self::$thanks_field_name; ?>" size="60"
                                                 value="<?php echo $thanks_value ?>"/></label></p>
                <p><label><?php echo __('Hide field error message') ?> : <input type="checkbox" name="<?php echo self::$field_error_field_name; ?>" size="60"
                                                                       value="1"<?php echo $field_error_value == '1' ? 'checked="checked"' : ''; ?>/></label></p>

				<?php wp_nonce_field( $this->textdomain, '__nonce' ); ?>
                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary"
                           value="<?php esc_attr_e( 'Save Changes' ) ?>"/>
                </p>
            </form>
        </div>
		<?php
	}
	*/

	/**
	 * activation hook
	 */
	function register_activation_hook() {
		if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			$e = new WP_Error( 'error', __( 'Please activate Contact Form 7 plugin in advance', 'contact-form-7-freebie' ) );
			set_transient( $this->textdomain . '-admin-errors', $e, 5 );
		}
	}

	function register_deactivation_hook() {
	}

	/**
	 * show error message
	 */
	function admin_notices() {
		if ( $e = get_transient( $this->textdomain . '-admin-errors' ) ) {
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
	function admin_init() {
		if ( get_transient( $this->textdomain . '-admin-errors' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			unset( $_GET['activate'] );
		}
	}
}

$partial_url = ContactForm7Freebie::get_instance();
