<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/2.0.1/okta-auth-js.min.js" type="text/javascript"></script>

<?php
    $options = defined('OKTA_OPTIONS') ? OKTA_OPTIONS : Okta\OktaSignIn::$OktaTemplateDefaults;
?>

<script>
  var authClient = new OktaAuth({
    url: '<?php echo $options['okta_base_url'] ?>',
    clientId: '<?php echo $options['okta_client_id'] ?>',
    issuer: '<?php echo $options['okta_base_url'] ?>/oauth2/<?php echo $options['okta_auth_server_id'] ?>',
  });

  authClient.signOut()
  .then(function() {
    window.location = '<?php echo home_url() ?>';
  }).catch(function(){
    window.location = '<?php echo home_url() ?>';
  });
</script>
