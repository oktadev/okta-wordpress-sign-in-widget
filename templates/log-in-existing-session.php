<script src="https://global.oktacdn.com/okta-signin-widget/4.1.3/js/okta-sign-in.min.js" type="text/javascript"></script>
<script>
var signIn = new OktaSignIn({
    baseUrl: '<?php echo get_option('okta-base-url') ?>',
    redirectUri: '<?php echo wp_login_url() ?>',
    el: '#widget-container',
    authParams: {
        clientId: '<?php echo get_option('okta-widget-client-id') ?>',
        display: 'page',
        issuer: '<?php echo get_option('okta-auth-server-id') ? (get_option('okta-base-url') . '/oauth2/' . get_option('okta-auth-server-id')) : get_option('okta-base-url') ?>'
    }
});

signIn.authClient.session.exists()
  .then(function(exists) {
    if(exists) {
      signIn.authClient.token.getWithoutPrompt({
        responseType: ['id_token'],
        scopes: ['openid', 'email']
      })
      .then(function(tokens){
        var url_to_redirect_to = '<?php echo wp_login_url() ?>' + '?log_in_from_id_token=' + tokens.tokens.idToken.value;
        window.location.href = url_to_redirect_to;
      });
   }
  });
</script>
