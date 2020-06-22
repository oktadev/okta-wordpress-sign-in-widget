<?php
define('OKTA_BASE_URL', 'https://youroktadomain.okta.com');

# Create a "SPA" client in Okta and include the client ID here.
# Set the redirect URI to Wordpress' wp-login.php URL.
define('OKTA_WIDGET_CLIENT_ID', '');

# Create a "Service" app and include the client ID and secret here.
define('OKTA_CLIENT_ID', '');
define('OKTA_CLIENT_SECRET', '');

# If you're using API Access Management, define the auth server ID below.
# Otherwise leave it commented out.
# define('OKTA_AUTH_SERVER_ID', 'default');
