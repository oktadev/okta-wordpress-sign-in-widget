<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/1.13.0/okta-auth-js.min.js" type="text/javascript"></script>
<script>
    var authClient = new OktaAuth({
      url: '<?= OKTA_BASE_URL ?>',
      clientId: '<?= OKTA_CLIENT_ID ?>',
      issuer: '<?= OKTA_BASE_URL ?>/oauth2/<?= OKTA_AUTH_SERVER_ID ?>',
    });

    authClient.signOut()
    .then(function() {
        window.location = '<?= home_url() ?>';
    });
</script>