<!-- load the Okta sign-in widget-->
<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-signin-widget/2.6.0/js/okta-sign-in.min.js" type="text/javascript"></script>
<link href="https://ok1static.oktacdn.com/assets/js/sdk/okta-signin-widget/2.6.0/css/okta-sign-in.min.css" type="text/css" rel="stylesheet"/>
<link href="https://ok1static.oktacdn.com/assets/js/sdk/okta-signin-widget/2.6.0/css/okta-theme.css" type="text/css" rel="stylesheet"/>

<div id="primary" class="content-area">
  <div id="widget-container"></div>
</div>

<script>
    var signIn = new OktaSignIn({
        baseUrl: '<?php echo OKTA_BASE_URL ?>',
        clientId: '<?php echo OKTA_CLIENT_ID ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        authParams: {
            issuer: '<?php echo defined('OKTA_AUTH_SERVER_ID') ? (OKTA_BASE_URL . '/oauth2/' . OKTA_AUTH_SERVER_ID) : OKTA_BASE_URL ?>',
            responseType: 'code',
            display: 'page',
            scopes: ['openid', 'email'],
            state: '<?php echo Okta\OktaSignIn::generateState() ?>'
        }
    });
    signIn.session.get(function(res) {
        signIn.renderEl({
                el: '#widget-container'
            },
            function success(res) {}
        );
    });
</script>
