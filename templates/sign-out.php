<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/1.13.0/okta-auth-js.min.js" type="text/javascript"></script>
<script>
    var authClient = new OktaAuth({
      url: '<?php echo OKTA_BASE_URL ?>',
      clientId: '<?php echo OKTA_CLIENT_ID ?>',
      issuer: '<?php echo OKTA_BASE_URL ?>/oauth2/<?php echo OKTA_AUTH_SERVER_ID ?>',
    });

    authClient.signOut()
    .then(function() {
        window.location = '<?php echo home_url() ?>';
    });
</script>
