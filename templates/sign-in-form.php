<!-- load the Okta sign-in widget-->
<script src="https://global.oktacdn.com/okta-signin-widget/4.1.3/js/okta-sign-in.min.js" type="text/javascript"></script>
<link href="https://global.oktacdn.com/okta-signin-widget/4.1.3/css/okta-sign-in.min.css" type="text/css" rel="stylesheet"/>

<div id="primary" class="content-area">
  <div id="widget-container"></div>
</div>

<script>
    var signIn = new OktaSignIn({
        baseUrl: '<?php echo OKTA_BASE_URL ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        el: '#widget-container',
        authParams: {
            display: 'page',
        }
    });
    if(signIn.hasTokensInUrl()) {
        // Grab the auth code from the URL and exchange it for an ID token
        signIn.authClient.token.parseFromUrl()
            .then(function (res) {
                // Redirect back here with the ID token in the URL. The backend will validate it and log the user in.
                window.location = '<?php echo wp_login_url() ?>?log_in_from_id_token='+res.tokens.idToken.value;
            });
    }
    else {
        signIn.showSignInToGetTokens({
            clientId: '<?php echo OKTA_WIDGET_CLIENT_ID ?>',
            getAccessToken: false,
            getIdToken: true,
            scope: 'openid email',
        });
    }
</script>
