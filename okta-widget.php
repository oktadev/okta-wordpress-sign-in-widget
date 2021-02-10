<?php
namespace Okta;

/**
 * Plugin Name: Okta Sign-In Widget
 * Plugin URI: https://github.com/oktadeveloper/okta-wordpress-sign-in-widget
 * Description: Log in to your site using the Okta Sign-In Widget
 * Version: 0.3.0
 * Author: Aaron Parecki, Tom Smith, Nico Triballier, Joël Franusic
 * Author URI: https://developer.okta.com/
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: okta
 * Domain Path: /languages
 */

include plugin_dir_path(__FILE__).'/includes/okta-admin.php';

class OktaSignIn
{
    private $OktaAdmin;
    private $base_url = false;
    private $introspection_endpoint = false;

    public function __construct()
    {
        $this->OktaAdmin = new OktaAdmin;

        $this->setBaseUrl();

        // https://developer.wordpress.org/reference/hooks/login_init/
        add_action('login_init', array($this, 'loginAction'));

        // This runs on every pageload to insert content into the HTML <head> section
        // https://codex.wordpress.org/Plugin_API/Action_Reference/wp_head
        add_action('wp_head', array($this, 'addLogInExistingSessionAction'));

        add_action('init', array($this, 'startSessionAction'));
    }

    private function setBaseUrl()
    {
        if($issuer = get_option('okta-issuer-url')) {
            $this->base_url = parse_url($issuer, PHP_URL_SCHEME).'://'.parse_url($issuer, PHP_URL_HOST);
        }
    }

    private function getIntrospectionEndpoint() {
        if($this->introspection_endpoint)
            return $this->introspection_endpoint;

        if(!$this->base_url)
            return false;

        $response = wp_remote_get(get_option('okta-issuer-url').'/.well-known/openid-configuration');
        if(!$response)
            return false;

        $metadata = json_decode($response['body'], true);
        if(!$metadata)
            return false;

        if(!isset($metadata['introspection_endpoint']))
            return false;

        return $this->introspection_endpoint = $metadata['introspection_endpoint'];
    }

    public function startSessionAction()
    {
        if (session_status() != PHP_SESSION_ACTIVE) {
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
        if (isset($_GET["action"]) && $_GET["action"] === "logout") {
            $this->logUserOutOfOkta();
        }

        if (isset($_GET['log_in_from_id_token'])) {
            $this->logUserIntoWordPressWithIDToken($_GET['log_in_from_id_token'], $redirect_to);
            exit;
        }

        if($this->useWordpressLogin()) {
            return;
        }

        // If there is no code in the query string, show the Okta sign-in widget
        $template = plugin_dir_path(__FILE__) . 'templates/sign-in-form.php';
        load_template($template);
        exit;
    }

    private function useWordpressLogin() 
    {
        // Always skip showing the Okta widget on POST requests
        if($_SERVER['REQUEST_METHOD'] === 'POST')
            return true;

        // If the plugin isn't configured yet, don't show the Okta widget
        if(!$this->base_url)
            return true;

        // null when plugin is not configured, "1"/"0" after
        if(get_option('okta-allow-wordpress-login') === null || get_option('okta-allow-wordpress-login') === "1")
        {
            if(isset($_GET['wordpress_login']) && $_GET['wordpress_login'] == 'true')
                return true;

            if(isset($_GET['action']) && $_GET['action'] == 'lostpassword')
                return true;

            if(isset($_GET['checkemail']))
                return true;
        }

        return false;
    }

    private function logUserOutOfOkta() {
        $user = wp_get_current_user();

        wp_clear_auth_cookie();

        $template = plugin_dir_path(__FILE__) . 'templates/sign-out.php';
        load_template($template);
        exit;
    }

    private function logUserIntoWordPressWithIDToken($id_token, $redirect_to)
    {
        $introspection_endpoint = $this->getIntrospectionEndpoint();

        if(!$this->introspection_endpoint)
            die("The plugin is not configured properly. Please double check the Issuer URI in the configuration.");

        /********************************************/
        // [jpf] TODO: Implement client-side id_token validation to speed up the verification process
        //             (~300ms for /introspect endpoint v. ~5ms for client-side validation)
        $payload = array(
            'client_id' => get_option('okta-widget-client-id'),
            'token' => $id_token,
            'token_type_hint' => 'id_token'
        );
        $response = $this->httpPost($this->introspection_endpoint, $payload);
        if ($response === false) {
            die("Invalid id_token received from Okta");
        }
        $claims = json_decode($response['body'], true);
        if (!$claims['active']) {
            die("Okta reports that id_token is not active or client authentication failed:" . $claims['error_description']);
        }
        /********************************************/
        
        $this->logUserIntoWordPressFromEmail($claims, $redirect_to);
    }

    private function logUserIntoWordPressFromEmail($claims, $redirect_to)
    {
        $email = $claims['email'];

        // Find or create the WordPress user for this email address
        $user = get_user_by('email', $email);
        if (!$user) {
            $random_password = wp_generate_password($length = 64, $include_standard_special_chars = false);
            $user_id = wp_create_user($email, $random_password, $email);
            $user = get_user_by('id', $user_id);
        } else {
            $user_id = $user->ID;
        }

        do_action('okta_widget_before_login', $claims, $user);

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
}

$okta = new OktaSignIn();
