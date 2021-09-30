<?php include plugin_dir_path(__FILE__).'/../includes/widget.php'; ?>

<script>
<?php include plugin_dir_path(__FILE__).'/../includes/initialize-widget.js.php'; ?>

oktaSignIn.authClient.tokenManager.get('idToken').then(function(idToken){
    if(idToken) {
      oktaSignIn.authClient.signOut({
          idToken: idToken,
          postLogoutRedirectUri: '<?php echo home_url() ?>'
      });
    } else {
      window.location = '<?php echo home_url() ?>';
    }
});
</script>
