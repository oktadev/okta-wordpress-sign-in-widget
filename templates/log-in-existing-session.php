<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/2.0.1/okta-auth-js.min.js" type="text/javascript"></script>

<?php
    $options = defined('OKTA_OPTIONS') ? OKTA_OPTIONS : Okta\OktaSignIn::$OktaTemplateDefaults;
?>

<script>
var authClient = new OktaAuth({
  url: '<?php echo $options['okta_base_url'] ?>',
  clientId: '<?php echo $options['okta_client_id'] ?>',
  redirectUri: '<?php echo wp_login_url() ?>',
  issuer: '<?php echo !empty($options['okta_auth_server_id']) ? ($options['okta_base_url'] . '/oauth2/' . $options['okta_auth_server_id']) : $options['okta_base_url'] ?>',
});

authClient.session.exists()
  .then(function(exists) {
    if (exists) {
      authClient.token.getWithoutPrompt({
        responseType: ['id_token', 'token'],
        scopes: ['openid', 'email']
      })
      .then(function(tokens){
        var url_to_redirect_to = '<?php echo wp_login_url() ?>' + '?log_in_from_id_token=' + tokens[0].idToken;
        window.location.href = url_to_redirect_to;
      });
   }
  });
</script>
<?php
