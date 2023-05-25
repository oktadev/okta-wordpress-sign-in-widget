# WordPress Okta Sign-In Widget

This plugin replaces the WordPress login screen with the Okta sign-in widget.

🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨

This plugin was created to demonstrate the capability of replacing the WordPress login screen with the Okta sign-in widget for [this 2018 blog post](https://developer.okta.com/blog/2018/10/30/wordpress-authentication-with-okta).

🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨

This plugin is not supported by Okta, and not updated regularly.

🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨

If you would like to use an officially supported Okta WordPress integration, please see [this guide](https://plugins.miniorange.com/okta-single-sign-on-wordpress-sso-oauth-openid-connect) to configuring the [miniOrange WordPress SSO Plugin](https://www.okta.com/integrations/wordpress-oauth-single-sign-on-sso-by-miniorange/) with Okta.

🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨🚨


## Setup Instructions

After dropping this folder into the WordPress plugins folder and activating the plugin, you should see a new Settings menu where you can configure your Okta settings to enable the plugin.

Make sure your admin user in WordPress has an email address that matches an Okta user, or enable native WordPress logins, otherwise you'll be locked out of your WordPress after configuring the plugin.

TODO:

* Clean up the UX around installing the plugin, like making sure the admin user can still log in after the plugin is activated
* Handle errors better (or at all really)

## Development Environment

### Manual

Install WordPress and move plugin to `wp-content/plugins` directory

### Docker

Install Docker and docker-compose and run `docker-compose up` 

Navigate to http://localhost:8080
