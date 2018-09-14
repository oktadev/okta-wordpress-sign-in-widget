<?php
/**
 * Plugin Name: Okta Sign-In Widget
 * Plugin URI: https://github.com/oktadeveloper
 * Description: Login to your site using the Okta Sign-In Widget
 * Version: 0.0.1
 * Author: Aaron Parecki
 * Author URI: https://developer.okta.com/
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: okta
 * Domain Path: /languages
 */

define( 'OKTA_BASE_URL', 'https://dev-000000.oktapreview.com' );
define( 'OKTA_CLIENT_ID', '' );
define( 'OKTA_CLIENT_SECRET', '' );

class OktaSignIn {

	public function __construct() {
		add_action( 'login_init', array( $this, 'login_form' ) );
	}

	public function login_form() {
		if( !isset( $_GET['code'] ) ) {
			$template = plugin_dir_path( __FILE__ ) . 'templates/sign-in-form.php';
			load_template( $template );
			exit;
		} else {
			// Determine who authenticated and start a WordPress session

			$endpoint = 
			$post_body = array(
				'grant_type' => 'authorization_code',
				'code' => $_GET['code'],
				'redirect_uri' => wp_login_url(),
				'client_id' => OKTA_CLIENT_ID,
				'client_secret' => OKTA_CLIENT_SECRET,
			);
			$args      = array(
				'headers' => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => $post_body,
			);
			$response  = wp_remote_post( OKTA_BASE_URL . '/oauth2/default/v1/token', $args );

			$body = json_decode( $response['body'], true );

			if( isset( $body['id_token'] ) ) {
				// We don't need to verify the JWT signature since it came from the HTTP response directly
				list($header, $payload, $signature) = explode( '.', $body['id_token'] );
				$claims = json_decode(base64_decode($payload), true);

				// Find or create the WordPress user for this email address
				$user = get_user_by( 'email', $claims['email'] );
				if( !$user ) {
					$random_password = wp_generate_password( $length=64, $include_standard_special_chars=false );
					$user_id = wp_create_user( $claims['email'], $random_password, $claims['email'] );
					$user = get_user_by( 'id', $user_id );
				} else {
					$user_id = $user->ID;
				}
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );
				do_action( 'wp_login', $user->user_login );
				wp_redirect( home_url() );

			} else {
				// Something went wrong
				echo 'There was an error logging in. Show a better error message here in the future.';
				exit;
			}

		}
	}
}

$okta = new OktaSignIn();

