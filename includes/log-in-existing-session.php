<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/2.0.1/okta-auth-js.min.js" type="text/javascript"></script>

<?php
    $default = [
        'OKTA_BASE_URL' => '',
        'OKTA_CLIENT_ID' => '',
        'OKTA_AUTH_SERVER_ID' => ''
    ];
    $options = defined('OKTA_OPTIONS') ? OKTA_OPTIONS : $default;
?>

<script>
var authClient = new OktaAuth({
  url: '<?php echo $options['OKTA_BASE_URL'] ?>',
  clientId: '<?php echo $options['OKTA_CLIENT_ID'] ?>',
  redirectUri: '<?php echo wp_login_url() ?>',
  issuer: '<?php echo !empty($options['OKTA_AUTH_SERVER_ID']) ? ($options['OKTA_BASE_URL'] . '/oauth2/' . $options['OKTA_AUTH_SERVER_ID']) : $options['OKTA_BASE_URL'] ?>',
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
