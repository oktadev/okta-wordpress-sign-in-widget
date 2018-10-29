<?php namespace Okta;

/**
 * Plugin Name: Okta Sign-In Widget
 * Plugin URI: https://github.com/oktadeveloper
 * Description: Log in to your site using the Okta Sign-In Widget
 * Version: 0.10.2
 * Author: Aaron Parecki, Tom Smith, Nico Triballier, Joel Franusic
 * Author URI: https://developer.okta.com/
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: okta
 * Domain Path: /languages
 */

class OktaSignIn
{

    public function __construct()
    {
        register_activation_hook( __FILE__, array( $this, 'activated' ) );

        // TODO: Refactor this after adding support
        //       for configuring this plugin via a settings page
        if(file_exists(plugin_dir_path(__FILE__) . "env.json")) {
          /*******************************/
          // Load environment variables
          $json = file_get_contents(plugin_dir_path(__FILE__) . "env.json");
          if ($json === false) {
              die("could not open env.json file");
          }
          $env = json_decode($json, true);
          foreach ($env as $k => $v) {
              define($k, $v);
          }
          // [jpf] FIXME: Add support for configuring this plugin via a settings page:
          //              https://codex.wordpress.org/Creating_Options_Pages
          /*******************************/

          $this->env = array(
              'OKTA_BASE_URL' => OKTA_BASE_URL,
              'OKTA_CLIENT_ID' => OKTA_CLIENT_ID,
              'OKTA_CLIENT_SECRET' => OKTA_CLIENT_SECRET,
              'OKTA_AUTH_SERVER_ID' => OKTA_AUTH_SERVER_ID
          );

          $this->base_url = sprintf(
              '%s/oauth2/%s/v1',
              $this->env['OKTA_BASE_URL'],
              $this->env['OKTA_AUTH_SERVER_ID']
          );
        }

        add_action('login_init', array($this, 'loginAction'));
        add_action('wp_head', array($this, 'addLogInExistingSessionAction'));
        add_action('init', array($this, 'startSessionAction'));
    }

    public function activated() {
      // TODO: remove this after adding support for configuring via settings page
      // Check for the existence of env.json
      if (!file_exists(plugin_dir_path(__FILE__) . "env.json")) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'Please copy env.example.json to env.json and fill in your Okta application details, then activate this plugin again.' );
      }
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
            include("includes/log-in-existing-session.php");
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
        $redirect_to = false;
        if (isset($_GET['redirect_to'])) {
            $redirect_to = $_GET['redirect_to'];
            $_SESSION['redirect_to'] = $_GET['redirect_to'];
        }

        if (isset($_GET["action"])) {
            if ($_GET["action"] === "logout") {
                $user = wp_get_current_user();

                wp_clear_auth_cookie();
                error_log("Logging out WordPress user with ID of: " . $user->ID);

                $template = plugin_dir_path(__FILE__) . 'templates/sign-out.php';
                load_template($template);
                exit;
            }
        }

        if (isset($_GET['log_in_from_id_token'])) {
            $this->logUserIntoWordPressWithIDToken($_GET['log_in_from_id_token'], $redirect_to);
            exit;
        }

        if (isset($_GET['code'])) {
            // If there is a code in the query string, look up the code at Okta to find out who logged in
            // Authorization code flow
            $payload = array(
                'grant_type' => 'authorization_code',
                'code' => $_GET['code'],
                'redirect_uri' => wp_login_url(),
                'client_id' => $this->env['OKTA_CLIENT_ID'],
                'client_secret' => $this->env['OKTA_CLIENT_SECRET'],
            );
            $response = $this->httpPost($this->base_url . '/token', $payload);
            $body = json_decode($response['body'], true);
            if (isset($body['id_token'])) {
                error_log("id_token passed via body");
                // Determine who authenticated and start a WordPress session
                $this->logUserIntoWordPressWithIDToken($body['id_token'], $_GET['redirect_to']);
            } else {
                die('There was an error logging in: ' . $body['error_description']);
            }
        }

        // If there is no code in the query string, show the Okta sign-in widget
        $template = plugin_dir_path(__FILE__) . 'templates/sign-in-form.php';
        load_template($template);
        exit;
    }

    private function logUserIntoWordPressWithIDToken($id_token, $redirect_to)
    {
        unset($_SESSION['user_id_token']);

        /********************************************/
        // [jpf] TODO: Implement client-side id_token validation to speed up the verification process
        //             (~300ms for /introspect endpoint v. ~5ms for client-side validation)
        $payload = array(
            'client_id' => $this->env['OKTA_CLIENT_ID'],
            'client_secret' => $this->env['OKTA_CLIENT_SECRET'],
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
        $_SESSION['user_id_token'] = $id_token;

        // Find or create the WordPress user for this email address
        $user = get_user_by('email', $claims['email']);
        if (!$user) {
            $random_password = wp_generate_password($length = 64, $include_standard_special_chars = false);
            $user_id = wp_create_user($claims['email'], $random_password, $claims['email']);
            $user = get_user_by('id', $user_id);
        } else {
            $user_id = $user->ID;
        }
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        error_log("Logging in WordPress user with ID of: " . $user_id);
        // See also: https://developer.wordpress.org/reference/functions/do_action/
        do_action('wp_login', $user->user_login);

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
