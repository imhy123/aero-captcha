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

if ( !function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) ) {
    die();
}

if ( !function_exists('write_log') ) {
    function write_log( $log )  {
        $log_content = $log;
        if ( is_array( $log ) || is_object( $log ) ) {
            $log_content = print_r( $log, true );
        }

        $date = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
        $date = $date->format("Y-m-d H:i:s ");

        $log_content = $date . $log_content . "\n";

        $log_file = fopen(ABSPATH . '/ac_log.txt', 'a');
        if ($log_file) {
            fwrite( $log_file, $log_content );
            fclose( $log_file );
        }
    }
 }

 function disable_ssl_verify( $handle, $request_args, $request_url ) {
    if ( false === strpos( $request_url, 'www.recaptcha.net' ) ) {
        return;
    }
    
    curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, false );
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
            add_action( 'http_api_curl', 'disable_ssl_verify', 10, 3 );

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
                write_log( 'secret_key is empty, return verify success' );

                return true;
            }

            $remoteip = $_SERVER['REMOTE_ADDR'];
            if ( ! $remoteip ) {
                write_log( 'remoteip is empty, return verify success' );

                return true;
            }

            $token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';

            if ( ! $token ) {
                write_log( 'IP: ' . $remoteip . ', token is empty, return verify failed' );

                return false;
            }

            write_log( 'start verify, remoteip: ' . $remoteip );

            $url = 'https://www.recaptcha.net/recaptcha/api/siteverify';

            // make a POST request to the Google reCAPTCHA Server
            $response = wp_remote_post(
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
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                write_log( 'post failed: ' . $error_message .  ', will return verify success' );

                return true;
            }

            $response_body = wp_remote_retrieve_body( $response );
            if ( ! $response_body ) {
                write_log( 'response_body is empty, return verify success' );

                return true;
            }

            $result = json_decode( $response_body, true );
            if ( isset( $result['success'] ) && true == $result['success'] ) {
                $score = isset( $result['score'] ) ? $result['score'] : 0;

                write_log( 'verify finished, remoteip: ' . $remoteip . ', score: ' . $score );
                
                return $score > 0.5;
            } else {
                write_log( 'verify failed, remoteip: ' . $remoteip . ', response_body: ' . $response_body);

                return false;
            }
		}
    }
}

add_action( 'init', array( 'AeroCaptcha', 'init' ) );