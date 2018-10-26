<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/1.13.0/okta-auth-js.min.js" type="text/javascript"></script>
<script>
var authClient = new OktaAuth({
  url: '<?= OKTA_BASE_URL ?>',
  clientId: '<?= OKTA_CLIENT_ID ?>',
  redirectUri: '<?= wp_login_url() ?>',
  issuer: '<?= OKTA_BASE_URL ?>/oauth2/<?= OKTA_AUTH_SERVER_ID ?>',
});

authClient.session.exists()
  .then(function(exists) {
    if (exists) {
      authClient.token.getWithoutPrompt({
        responseType: ['id_token', 'token'],
        scopes: ['openid', 'email']
      })
        .then(function(tokens){
          var url_to_redirect_to = '<?= wp_login_url() ?>' + '?id_token=' + tokens[0].idToken;
          window.location.href = url_to_redirect_to;
        })
   }
  });
</script>
<?php
