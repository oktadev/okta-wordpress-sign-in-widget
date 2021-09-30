<?php include plugin_dir_path(__FILE__).'/../includes/widget.php'; ?>

<script>
<?php include plugin_dir_path(__FILE__).'/../includes/initialize-widget.js.php'; ?>

oktaSignIn.authClient.token.getUserInfo().then(function(user) {
    oktaSignIn.authClient.tokenManager.get('idToken').then(function(idToken){
        oktaSignIn.authClient.signOut({
            idToken: idToken,
            postLogoutRedirectUri: '<?php echo home_url() ?>'
        });
    });
}, function(error) {
    window.location = '<?php echo home_url() ?>';
});
</script>
