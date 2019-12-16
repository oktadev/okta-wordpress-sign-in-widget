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
      issuer: '<?php echo $options['OKTA_BASE_URL'] ?>/oauth2/<?php echo $options['OKTA_AUTH_SERVER_ID'] ?>',
    });

    authClient.signOut()
    .then(function() {
      window.location = '<?php echo home_url() ?>';
    }).catch(function(){
      window.location = '<?php echo home_url() ?>';
    });
</script>
