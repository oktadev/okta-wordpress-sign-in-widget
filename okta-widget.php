<?php
namespace Okta;

/**
 * Plugin Name: Okta Sign-In Widget
 * Plugin URI: https://github.com/oktadeveloper
 * Description: Log in to your site using the Okta Sign-In Widget
 * Version: 0.11.0
 * Author: Aaron Parecki, Tom Smith, Nico Triballier, Joël Franusic
 * Author URI: https://developer.okta.com/
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: okta
 * Domain Path: /languages
 */

class OktaSignIn
{
    private $OktaAdmin;
    private $OktaOptions;
    public static $OktaTemplateDefaults = [
        'okta_base_url' => '',
        'okta_client_id' => '',
        'okta_auth_server_id' => '',
        'okta_wordpress_login' => 0
    ];

    public function __construct()
    {
        $this->setup_constants();
        $this->includes();

        $this->getOktaOptions();
        $this->setupOkta();

        // https://developer.wordpress.org/reference/hooks/login_init/
        add_action('login_init', array($this, 'loginAction'));

        // This runs on every pageload to insert content into the HTML <head> section
        // https://codex.wordpress.org/Plugin_API/Action_Reference/wp_head
        add_action('wp_head', array($this, 'addLogInExistingSessionAction'));


        add_action('init', array($this, 'startSessionAction'));
    }

    private function setup_constants(){
        if ( ! defined( 'OKTA_PLUGIN_PATH' ) ) {
            define( 'OKTA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
        }
    }

    private function includes(){
        include OKTA_PLUGIN_PATH . 'includes/okta-admin.php';

        $this->OktaAdmin = new OktaAdmin;
    }

    private function getOktaOptions(){
        $opts = get_option($this->OktaAdmin->optionSetName);
        if(!empty($opts)){
            $this->OktaOptions = $opts;
        }
        define('OKTA_OPTIONS', $this->OktaOptions);
    }

    private function setupOkta(){

        if($this->OktaOptions['okta_auth_server_id']) {
            $this->base_url = sprintf(
                '%s/oauth2/%s/v1',
                $this->OktaOptions['okta_base_url'],
                $this->OktaOptions['okta_auth_server_id']
            );
        } else {
            $this->base_url = sprintf(
                '%s/oauth2/v1',
                $this->OktaOptions['okta_base_url']
            );
        }

    }

    public static function generateState() {
        return $_SESSION['okta_state'] = wp_generate_password(64, false);
    }

    public function startSessionAction()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public function addLogInExistingSessionAction()
    {
        if (!is_user_logged_in()) {
            $this->startSessionAction();
            $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
            include("templates/log-in-existing-session.php");
        }
    }

    private function httpPost($url, $body)
    {
        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => $body,
        );
        return wp_remote_post($url, $args);
    }

    public function loginAction()
    {

        // Support redirecting back to the page the user was on before they clicked log in
        $redirect_to = false;
        if (isset($_GET['redirect_to'])) {
            $redirect_to = $_GET['redirect_to'];
            $_SESSION['redirect_to'] = $_GET['redirect_to'];
        }

        // When signing out of WordPress, tell the Okta JS library to log out of Okta as well
        if (isset($_GET["action"])) {
            $this->logUserOutOfOkta($_GET['action']);
        }

        if (isset($_GET['log_in_from_id_token'])) {
            $this->logUserIntoWordPressWithIDToken($_GET['log_in_from_id_token'], $redirect_to);
            exit;
        }

        if (isset($_GET['code'])) {
            $this->oktaCheckToken($_GET['code']);
        }

        if($this->isWordpressLoginAvailable()){
            return;
        }

        // If there is no code in the query string, show the Okta sign-in widget
        $template = OKTA_PLUGIN_PATH . 'templates/sign-in-form.php';
        load_template($template);
        exit;
    }

    private function oktaCheckToken($code){
        // If there is a code in the query string, look up the code at Okta to find out who logged in

        // First verify that the state matches
        if($_GET['state'] != $_SESSION['okta_state']) {
            die('State error. Make sure cookies are enabled.');
        }

        // Authorization code flow
        $payload = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => wp_login_url(),
            'client_id' => $this->OktaOptions['okta_client_id'],
            'client_secret' => $this->OktaOptions['okta_client_secret'],
        );
        $response = $this->httpPost($this->base_url . '/token', $payload);
        $body = json_decode($response['body'], true);
        if (isset($body['id_token'])) {
            // We don't need to verify the JWT signature since it came from the HTTP response directly
            list($jwtHeader, $jwtPayload, $jwtSignature) = explode( '.', $body['id_token'] );
            $claims = json_decode(base64_decode($jwtPayload), true);
            
            $this->logUserIntoWordPressFromEmail($claims['email'], $_GET['redirect_to']);
        } else {
            die('There was an error logging in: ' . $body['error_description']);
        }
    }

    private function isWordpressLoginAvailable(){
        $available = false;

        if(!$this->OktaOptions['okta_wordpress_login']){
            return $available;
        }

        if(isset($_GET['wordpress_login']) && $_GET['wordpress_login'] || $_SERVER['REQUEST_METHOD'] === 'POST'){
            $available = true;
        }

        return $available;
    }

    private function logUserOutOfOkta($action){
        if ($action === "logout") {
            $user = wp_get_current_user();

            wp_clear_auth_cookie();

            $template = OKTA_PLUGIN_PATH . 'templates/sign-out.php';
            load_template($template);
            exit;
        }
    }

    private function logUserIntoWordPressWithIDToken($id_token, $redirect_to)
    {
        /********************************************/
        // [jpf] TODO: Implement client-side id_token validation to speed up the verification process
        //             (~300ms for /introspect endpoint v. ~5ms for client-side validation)
        $payload = array(
            'client_id' => $this->OktaOptions['okta_client_id'],
            'client_secret' => $this->OktaOptions['okta_client_secret'],
            'token' => $id_token,
            'token_type_hint' => 'id_token'
        );
        $response = $this->httpPost($this->base_url . '/introspect', $payload);
        if ($response === false) {
            die("Invalid id_token received from Okta");
        }
        $claims = json_decode($response['body'], true);
        if (!$claims['active']) {
            die("Okta reports that id_token is not active:" . $claims['error_description']);
        }
        /********************************************/

        // error_log("Got claims:");
        // error_log(print_r($claims, true));

        $this->logUserIntoWordPressFromEmail($claims['email'], $redirect_to);
    }

    private function logUserIntoWordPressFromEmail($email, $redirect_to)
    {
        // Find or create the WordPress user for this email address
        $user = get_user_by('email', $email);
        if (!$user) {
            $random_password = wp_generate_password($length = 64, $include_standard_special_chars = false);
            $user_id = wp_create_user($email, $random_password, $email);
            $user = get_user_by('id', $user_id);
        } else {
            $user_id = $user->ID;
        }

        // Actually log the user in now
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        error_log("Logging in WordPress user with ID of: " . $user_id);

        // See also: https://developer.wordpress.org/reference/functions/do_action/
        // Run the wp_login actions now that the user is logged in
        do_action('wp_login', $user->user_login, $user);

        if (isset($_SESSION['redirect_to'])) {
            $redirect_uri = $_SESSION['redirect_to'];
            unset($_SESSION['redirect_to']);
        } else {
            $redirect_uri = home_url();
        }
        wp_redirect($redirect_uri);
    }

    public static function getWordpressLoginUrl(){
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $proto = 'https'; 
        } else {
            $proto = 'http';
        } 

        $base = $proto.'://'.$_SERVER['HTTP_HOST'];
        $full = $base.'/wp-login.php?wordpress_login=true';
        return $full;
    }
}

$okta = new OktaSignIn();
