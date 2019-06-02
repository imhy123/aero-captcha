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
            //add_action( 'admin_notices', array( $this, 'admin_notices' ) );
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

            /* system values to determine if captcha is working and display useful error messages */
            /*
            delete_option('login_nocaptcha_working');

            add_option('login_nocaptcha_error', 
                sprintf(__('Login NoCaptcha has not been properly configured. <a href="%s">Click here</a> to configure.','login-recaptcha'), 'options-general.php?page=login-recaptcha/admin.php'));
            add_option('login_nocaptcha_message_type', 'notice-error');
            
            if (LoginNocaptcha::valid_key_secret(get_option('login_nocaptcha_key')) &&
            LoginNocaptcha::valid_key_secret(get_option('login_nocaptcha_secret')) ) {
                update_option('login_nocaptcha_working', true);
            } else {
                delete_option('login_nocaptcha_working');
                update_option('login_nocaptcha_message_type', 'notice-error');
                update_option('login_nocaptcha_error', sprintf(__('Login NoCaptcha has not been properly configured. <a href="%s">Click here</a> to configure.','login-recaptcha'), 'options-general.php?page=login-recaptcha/admin.php'));
            }
            */
        }
    }
}

AeroCaptcha::init();