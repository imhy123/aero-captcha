<?php
/*
Plugin Name: Aero Captcha
Plugin URI: 
Description: A simple captcha plugin.
Version: 1.0.0
Author: Spirit
Author URI: https://github.com/imhy123/AeroCaptcha
License: GPLv2 or later
*/

if ( !function_exists( 'add_action' ) ) {
    die();
}

if ( ! class_exists( 'AeroCaptcha' ) ) {
    class AeroCaptcha {

        private static $instance;

        public static function init() {
            if ( ! self::$instance instanceof self ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public static function filter_string( $string ) {
            return trim(filter_var($string, FILTER_SANITIZE_STRING)); //must consist of valid string characters
        }

        private function __construct() {
            $this->init_action();
        }

        private function init_action() {
            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
            add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );

            if ( ! is_user_logged_in() ) {
                add_action( 'comment_form_after_fields', array( $this, 'show_recaptcha_field_comment' ), 99 );
                add_filter( 'pre_comment_approved', array( $this, 'comment_verify' ), 99 );
            }
        }

        public function load_textdomain() {
            //load_plugin_textdomain( 'aero-captcha', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }

        public function register_menu_page(){
            add_options_page( __('Aero Catpcha Options','aero-captcha'), __('Aero Catpcha','aero-captcha'), 'manage_options', 
                plugin_dir_path(  __FILE__ ).'admin.php');
        }

        public function register_settings() {

            /* user-configurable values */
            add_option('aero_captcha_key', '');
            add_option('aero_captcha_secret', '');
            add_option('aero_captcha_whitelist', '');

            /* user-configurable value checking public static functions */
            register_setting( 'aero_captcha', 'aero_captcha_key', 'AeroCaptcha::filter_string' );
            register_setting( 'aero_captcha', 'aero_captcha_secret', 'AeroCaptcha::filter_string' );
            register_setting( 'aero_captcha', 'aero_captcha_whitelist', 'AeroCaptcha::filter_whitelist' );

        }

        public function show_recaptcha_field_comment() {
            $this->show_recaptcha_field('comment');
        }

        public function show_recaptcha_field($action) {
            echo $this->recaptcha_form_field();

            $this->recaptcha_js_field($action);
        }

        public function recaptcha_form_field() {
            $field = '<div class="aero_captcha_field">';
			$field .= '<input type="hidden" name="g-recaptcha-response" value="" />';
            $field .= '</div>';

            return $field;
        }

        public function recaptcha_js_field($action) {
            $site_key = trim( get_option( 'aero_captcha_key' ) );
            $google_url = 'https://www.recaptcha.net/recaptcha/api.js?render=' . $site_key;

            ?>
			<script src="<?php echo esc_url( $google_url ); ?>"></script>
            <script>
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo esc_js( $site_key ); ?>', {action: '<?php echo esc_js( $action ); ?>'}).then(function(token) {
                    console.log("getToken: " + token);

                    for ( var i = 0; i < document.forms.length; i++ ) {
                        var form = document.forms[i];
                        var captcha = form.querySelector( 'input[name="g-recaptcha-response"]' );
                        if ( null === captcha )
                            continue;

                        captcha.value = token;
                    }
                });
            });
            </script>

            <?php
        }

        function comment_verify( $approved ) {
			if ( ! $this->verify() ) {
				return new WP_Error( 'aero-captcha error', 'verify failed', 403 );
            }
            
			return $approved;
        }
        
        function verify() {
			if ( is_user_logged_in() ) {
				return true;
			}

            $secret_key = trim( get_option( 'aero_captcha_secret' ) );

            if ( ! $secret_key ) {
                return true;
            }

            $remoteip = $_SERVER['REMOTE_ADDR'];
            $token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';

            if ( ! $remoteip || ! $token ) {
                return false;
            }

            $url = 'https://www.recaptcha.net/recaptcha/api/siteverify';

            // make a POST request to the Google reCAPTCHA Server
            $request = wp_remote_post(
                $url, array(
                    'timeout' => 10,
                    'body'    => array(
                        'secret'   => $secret_key,
                        'response' => $token,
                        'remoteip' => $remoteip,
                    ),
                )
            );

            // get the request response body
            $response_body = wp_remote_retrieve_body( $request );
            if ( ! $response_body ) {
                return true;
            }

            $result = json_decode( $response_body, true );
            if ( isset( $result['success'] ) && true == $result['success'] ) {
                $score = isset( $result['score'] ) ? $result['score'] : 0;

                error_log( 'score: ' . $score );
                
                return $score > 0.5;
            } else {
                return false;
            }
		}
    }
}

add_action( 'init', array( 'AeroCaptcha', 'init' ) );