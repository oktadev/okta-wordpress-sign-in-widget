<?php

namespace Okta;

/**
 * Plugin Name: WP Okta Auth
 * Plugin URI: https://github.com/skwid138/okta-wordpress-sign-in-widget
 * Description: Allow users the option of authenticating via <a href="https://www.okta.com">Okta</a> instead of WordPress
 * Version: 0.0.4
 * Author: <a href="https://github.com/skwid138">Hunter Rancourt</a>, <a href="https://github.com/aaronpk">Aaron Parecki</a>, Tom Smith, Nico Triballier, and Joël Franusic
 * Author URI: https://www.capinfogroup.com/ and https://developer.okta.com/
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 */

class Okta_Sign_In {
	/* @var Okta_Sign_In $instance A reference to the single instance of this class */
	protected static $instance;

	/* @var string $base_url The domain of your okta app. 'https://dev-206470.oktapreview.com/' seems to be standard for okta developer accounts */
	protected $base_url;

	/* @var string $client_id From the Okta application Client Credentials this should match the 'Client ID' field */
	protected $client_id;

	/* @var string $client_secret From the Okta application Client Credentials this should match the 'Client secret' field */
	protected $client_secret;

	/* @var string $auth_server_id If this is not used it must be set to 'default' otherwise the JS uses it and will fail */
	protected $auth_server_id;

	/* @var string $endpoint The combination of $base_url, $auth_server_id to create the Okta endpoint */
	protected $endpoint;

	/* @var string $plugin_path The absolute path to the plugin's directory */
	protected $plugin_path;

	public function __construct() {
		$this->base_url = get_option('okta-base-url', 'https://dev-206470.oktapreview.com/');
		$this->client_id = get_option('okta-client-id');
		$this->client_secret = get_option('okta-client-secret');
		$this->auth_server_id = get_option('okta-auth-server-id', 'default');
		$this->plugin_path = plugin_dir_path(__FILE__);
	}

	/**
	 * Call this method after getting the instance
	 */
	public function init() {
		$this->admin_actions();

		// Make sure these have values set
		if ($this->base_url && $this->client_id && $this->client_secret) {
			// Update base_url
			$this->endpoint = "{$this->base_url}/oauth2/{$this->auth_server_id}/v1";
			$this->public_actions();
		}
	}

	/**
	 * Provides access to a single instance of this class.
	 *
	 * @return Okta_Sign_In A single instance of this class.
	 */
	public static function get_instance() {
		return self::$instance ?? new self;
	}

	/**
	 * Setup Public facing Actions
	 */
	protected function public_actions() {
		add_action('login_init', [$this, 'login_action']);
		add_action('wp_head', [$this, 'add_log_in_existing_session_action']);
		add_action('init', [$this, 'start_session_action']);
	}

	/**
	 * Setup Admin related actions
	 */
	protected function admin_actions() {
		if (is_admin()) {
			add_action('admin_init', [$this, 'register_settings_action']);
			add_action('admin_menu', [$this, 'options_menu_action']);
		}
	}

	/**
	 * Set okta_state property of Session
	 */
	public static function generate_state() {
		return $_SESSION['okta_state'] = wp_generate_password(64, false);
	}

	/**
	 * Register Plugin Settings and create the setting's fields to be used in WP admin
	 */
	public function register_settings_action() {
		add_settings_section(
			'okta-sign-in-widget-options-section',
			'Client Credentials',
			null,
			'okta-sign-in-widget');

		register_setting(
			'okta-sign-in-widget',
			'okta-base-url',
			['type' => 'string', 'description' => 'ie. https://youroktadomain.okta.com', 'show_in_rest' => false]);

		add_settings_field(
			'okta-base-url',
			'Base URL',
			[$this, 'options_page_text_input_action'],
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section',
			['name' => 'okta-base-url', 'default_value' => $this->base_url, 'type' => 'text', 'description' => 'ie. https://youroktadomain.okta.com']);

		register_setting(
			'okta-sign-in-widget',
			'okta-client-id',
			['type' => 'string', 'description' => 'Using the new developer console open the application\'s general tab and retrieve the Client ID from the Client Credentials section', 'show_in_rest' => false]);

		add_settings_field(
			'okta-client-id',
			'Client ID',
			[$this, 'options_page_text_input_action'],
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section',
			['name' => 'okta-client-id', 'default_value' => $this->client_id, 'type' => 'text', 'description' => 'Using the new developer console open the application\'s general tab and retrieve the Client ID from the Client Credentials section']);

		register_setting(
			'okta-sign-in-widget',
			'okta-client-secret',
			['type' => 'string', 'description' => 'Using the new developer console open the application\'s general tab and retrieve the Client secret from the Client Credentials section', 'show_in_rest' => false]);

		add_settings_field(
			'okta-client-secret',
			'Client secret',
			[$this, 'options_page_text_input_action'],
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section',
			['name' => 'okta-client-secret', 'default_value' => $this->client_secret, 'type' => 'text', 'description' => 'Using the new developer console open the application\'s general tab and retrieve the Client secret from the Client Credentials section']);

		register_setting(
			'okta-sign-in-widget',
			'okta-auth-server-id',
			['type' => 'string', 'description' => 'If you\'re using API Access Management, input the auth server ID; otherwise leave this blank', 'show_in_rest' => false, 'default' => false]);

		add_settings_field(
			'okta-auth-server-id',
			'Auth server ID',
			[$this, 'options_page_text_input_action'],
			'okta-sign-in-widget',
			'okta-sign-in-widget-options-section',
			['name' => 'okta-auth-server-id', 'default_value' => $this->auth_server_id, 'type' => 'text', 'description' => 'If you\'re using API Access Management, input the auth server ID; otherwise leave this set to "default"']);
	}

	/**
	 * Add Options Page to WP Admin
	 */
	public function options_menu_action() {
		add_options_page(
			'WP Okta Auth Options',
			'WP Okta Auth',
			'manage_options',
			'okta-sign-in-widget',
			[$this, 'options_page_action']);
	}

	/**
	 * If admin user output options page
	 */
	public function options_page_action() {
		if (current_user_can('manage_options'))  {
			include("{$this->plugin_path}includes/options-form.php");
		} else {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}
	}

	/**
	 * Assemble Markup for Admin Options Page
	 *
	 * @param string $option_name The name of the option
	 * @param string $type The type of field the input should be
	 */
	public function options_page_text_input_action($args) {
		$option_value = get_option($args['name'], $args['default_value']);

		echo("<input type=\"{$args['type']}\" id=\"{$args['name']}\" name=\"{$args['name']}\" value=\"{$option_value}\" autocomplete=\"off\" style=\"width:400px;\" required />
				<div style=\"padding: 0 5px; font-size: .7rem\">{$args['description']}</div>");
	}

	/**
	 *
	 */
	public function start_session_action() {
		if (!session_id()) {
			session_start();
		}
	}

	/**
	 *
	 */
	public function add_log_in_existing_session_action() {
		if (!is_user_logged_in()) {
			$this->start_session_action();
			$_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
			include("{$this->plugin_path}includes/log-in-existing-session.php");
		}
	}

	/**
	 * @param string $url The API URI
	 * @param array $body Payload to be sent to API
	 * @return array|\WP_Error
	 */
	private function http_post($url, $body) {
		$args = [
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
			],
			'body' => $body,
		];

		return wp_remote_post($url, $args);
	}

	/**
	 * This runs on login_init hook
	 */
	public function login_action() {
		// Support redirecting back to the page the user was on before they clicked log in
		$redirect_to = $_GET['redirect_to'] ?? false;
		if ($redirect_to) {
			$_SESSION['redirect_to'] = $_GET['redirect_to'];
		}

		// When signing out of WordPress, tell the Okta JS library to log out of Okta as well
		if (isset($_GET['action']) && $_GET['action'] === 'logout') {
			$user = wp_get_current_user();
			wp_clear_auth_cookie();
			$template = "{$this->plugin_path}templates/sign-out.php";
			load_template($template);
			exit;
		}

		if (isset($_GET['log_in_from_id_token'])) {
			$this->log_user_into_wordpress_with_id_token($_GET['log_in_from_id_token']);
			exit;
		}

		// If there is a code in the query string, look up the code at Okta to find out who logged in
		if (isset($_GET['code'])) {
			// First verify that the state matches
			if ($_GET['state'] != $_SESSION['okta_state']) {
				die('State error. Make sure cookies are enabled.');
			}

			// Authorization code flow
			$payload = [
				'grant_type' => 'authorization_code',
				'code' => $_GET['code'],
				'redirect_uri' => wp_login_url(),
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
			];

			$response = $this->http_post("{$this->endpoint}/token", $payload);
			$body = json_decode($response['body'], true);
			if (isset($body['id_token'])) {
				// We don't need to verify the JWT signature since it came from the HTTP response directly
				list($jwtHeader, $jwtPayload, $jwtSignature) = explode( '.', $body['id_token']);
				$claims = json_decode(base64_decode($jwtPayload), true);
				$this->log_user_into_wordpress_from_email($claims['email']);

			} else {
				die("There was an error logging in: {$body['error_description']}");
			}
		}

		// If there is no code in the query string, show the Okta sign-in widget
		$template = plugin_dir_path(__FILE__) . 'templates/sign-in-form.php';
		load_template($template);
		exit;
	}

	/**
	 * Get User email from Okta and log the user into WordPress
	 *
	 * @param string $id_token The...
	 * @param string $redirect_to The URL the user was on before hand.
	 */
	private function log_user_into_wordpress_with_id_token($id_token) {
		// [jpf] TODO: Implement client-side id_token validation to speed up the verification process
		//             (~300ms for /introspect endpoint v. ~5ms for client-side validation)
		$payload =  [
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'token' => $id_token,
			'token_type_hint' => 'id_token',
		];

		$response = $this->http_post("{$this->endpoint}/introspect", $payload);
		if ($response === false) {
			die('Invalid id_token received from Okta');
		}

		$claims = json_decode($response['body'], true);
		if (!$claims['active']) {
			die("Okta reports that id_token is not active: {$claims['error_description']}");
		}

		$this->log_user_into_wordpress_from_email($claims['email']);
	}

	/**
	 * Set the current WP User via the user email returned from Okta
	 * If the user doesn't exist in the WP DB create a user in WP
	 *
	 * @param string $email The user's email address
	 * @param string $redirect_to The URL a user was on prior to login
	 */
	private function log_user_into_wordpress_from_email($email) {
		$user = get_user_by('email', $email);

		// Create a user in WP
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
		error_log("Logging in WordPress user with ID of: {$user_id}");

		// Run the wp_login actions now that the user is logged in
		do_action('wp_login', $user->user_login);

		// Remove session redirect and redirect the user
		$redirect_uri = $_SESSION['redirect_to'] ?? home_url();
		unset($_SESSION['redirect_to']);
		wp_redirect($redirect_uri);
	}
}

// Get single instance of class and run the init method
$okta = Okta_Sign_In::get_instance();
$okta->init();