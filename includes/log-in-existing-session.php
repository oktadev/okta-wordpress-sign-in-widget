<?php
/**
 * What does this do?
 */
?>
<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/1.13.0/okta-auth-js.min.js" type="text/javascript"></script>
<script>
var authClient = new OktaAuth({
  url: '<?= (get_option('okta-base-url')) ?>',
  clientId: '<?= (get_option('okta-client-id')) ?>',
  redirectUri: '<?= (wp_login_url()) ?>',
  issuer: '<?= (get_option('okta-base-url')) ?>/oauth2/<?= (get_option('okta-auth-server-id')) ?>',
});

authClient.session.exists()
  .then(function(exists) {
    if (exists) {
      authClient.token.getWithoutPrompt({
        responseType: ['id_token', 'token'],
        scopes: ['openid', 'email']
      })
      .then(function(tokens){
        var url_to_redirect_to = '<?= (wp_login_url()) ?>' + '?log_in_from_id_token=' + tokens[0].idToken;
        window.location.href = url_to_redirect_to;
      });
   }
  });
</script>