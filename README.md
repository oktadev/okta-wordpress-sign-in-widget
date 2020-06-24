# WordPress Okta Sign-In Widget

Replaces the WordPress login screen with the Okta sign-in widget

This is just a proof of concept!

After dropping this folder into the WordPress plugins folder, you should see a new Settings menu where you can configure your Okta settings to enable the plugin.

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
