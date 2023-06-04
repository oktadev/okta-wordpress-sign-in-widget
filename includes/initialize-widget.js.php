    var oktaSignIn = new OktaSignIn({
        baseUrl: '<?php echo parse_url($issuer = get_option('okta-issuer-url'), PHP_URL_SCHEME).'://'.parse_url($issuer, PHP_URL_HOST) ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        clientId: '<?php echo get_option('okta-widget-client-id') ?>',
        scopes: '<?php echo apply_filters( 'okta_widget_token_scope', 'openid email profile') ?>'.split(' '),
        authParams: {
            issuer: '<?php echo get_option('okta-issuer-url') ?>'
        },
features: {
    registration: '<?php echo get_option('okta-allow-self-registration') ?>',
     },
customButtons: [
    <?php echo get_option('okta-custom-login-buttons') ?>
  ],
 logo: '<?php echo get_option('okta-login-logo-url') ?>'
    });