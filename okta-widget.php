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
	public function __construct()
	{
		// https://codex.wordpress.org/Creating_Options_Pages
		add_action('admin_init', array($this, 'registerSettingsAction'));
		// https://codex.wordpress.org/Adding_Administration_Menus
		add_action('admin_menu', array($this, 'optionsMenuAction'));
		$this->env = array(
			'OKTA_BASE_URL' => get_option('okta-base-url'),
			'OKTA_CLIENT_ID' => get_option('okta-client-id'),
			'OKTA_CLIENT_SECRET' => get_option('okta-client-secret'),
			'OKTA_AUTH_SERVER_ID' => get_option('okta-auth-server-id'),
		);
		// If options are defined by user
		if (
			!empty($this->env['OKTA_BASE_URL']) &&
			!empty($this->env['OKTA_CLIENT_ID']) &&
			!empty($this->env['OKTA_CLIENT_SECRET']) &&
			!empty($this->env['OKTA_AUTH_SERVER_ID'])
		) {
			$this->base_url = sprintf(
				'%s/oauth2/%s/v1',
				$this->env['OKTA_BASE_URL'],
				$this->env['OKTA_AUTH_SERVER_ID']
			);
			// https://developer.wordpress.org/reference/hooks/login_init/
			add_action('login_init', array($this, 'loginAction'));
			// This runs on every pageload to insert content into the HTML <head> section
			// https://codex.wordpress.org/Plugin_API/Action_Reference/wp_head
			add_action('wp_head', array($this, 'addLogInExistingSessionAction'));
			add_action('init', array($this, 'startSessionAction'));
		}
	}
	public static function generateState() {
		return $_SESSION['okta_state'] = wp_generate_password(64, false);
	}
	public function registerSettingsAction() {
		add_settings_section(
			'okta-sign-in-widget-options-section',
			'Client Credentials',
			null,
			'okta-sign-in-widget'
		);
		register_setting('okta-sign-in-widget', 'okta-base-url', array(
			'type' => 'string',
			'show_in_rest' => false,
		));
		add_settings_field(
			'okta-base-url',
			'Base URL',
			function() { $this->optionsPageTextInputAction('okta-base-url', 'text'); },
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section'
		);
		register_setting('okta-sign-in-widget', 'okta-client-id', array(
			'type' => 'string',
			'show_in_rest' => false,
		));
		add_settings_field(
			'okta-client-id',
			'Client ID',
			function() { $this->optionsPageTextInputAction('okta-client-id', 'text'); },
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section'
		);
		register_setting('okta-sign-in-widget', 'okta-client-secret', array(
			'type' => 'string',
			'show_in_rest' => false,
		));
		add_settings_field(
			'okta-client-secret',
			'Client secret',
			function() { $this->optionsPageTextInputAction('okta-client-secret', 'password'); },
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section'
		);
		register_setting('okta-sign-in-widget', 'okta-auth-server-id', array(
			'type' => 'string',
			'show_in_rest' => false,
		));
		add_settings_field(
			'okta-auth-server-id',
			'Auth server ID',
			function() { $this->optionsPageTextInputAction('okta-auth-server-id', 'text'); },
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section'
		);
	}
	public function optionsMenuAction() {
		add_options_page(
			'Okta Sign-In Widget Options',
			'Okta Sign-In Widget',
			'manage_options',
			'okta-sign-in-widget',
			array($this, 'optionsPageAction')
		);
	}
	public function optionsPageAction() {
		if (current_user_can('manage_options'))  {
			include("includes/options-form.php");
		} else {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}
	}
	public function optionsPageTextInputAction($option_name, $type) {
		$option_value = get_option($option_name, '');
		printf(
			'<input type="%s" id="%s" name="%s" value="%s" autocomplete="off" />',
			esc_attr($type),
			esc_attr($option_name),
			esc_attr($option_name),
			esc_attr($option_value)
		);
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
		// Support redirecting back to the page the user was on before they clicked log in
		$redirect_to = false;
		if (isset($_GET['redirect_to'])) {
			$redirect_to = $_GET['redirect_to'];
			$_SESSION['redirect_to'] = $_GET['redirect_to'];
		}
		// When signing out of WordPress, tell the Okta JS library to log out of Okta as well
		if (isset($_GET["action"])) {
			if ($_GET["action"] === "logout") {
				$user = wp_get_current_user();
				wp_clear_auth_cookie();
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
			// First verify that the state matches
			if($_GET['state'] != $_SESSION['okta_state']) {
				die('State error. Make sure cookies are enabled.');
			}
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
				// We don't need to verify the JWT signature since it came from the HTTP response directly
				list($jwtHeader, $jwtPayload, $jwtSignature) = explode( '.', $body['id_token'] );
				$claims = json_decode(base64_decode($jwtPayload), true);
				$this->logUserIntoWordPressFromEmail($claims['email'], $_GET['redirect_to']);
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