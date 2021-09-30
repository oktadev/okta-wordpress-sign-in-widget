<?php include plugin_dir_path(__FILE__).'/../includes/widget.php'; ?>

<script>
<?php include plugin_dir_path(__FILE__).'/../includes/initialize-widget.js.php'; ?>

oktaSignIn.authClient.session.exists()
  .then(function(exists) {
    if(exists) {
      oktaSignIn.authClient.token.getWithoutPrompt({
        responseType: ['id_token'],
      })
      .then(function(tokens){
        window.location.href = '<?php echo wp_login_url() ?>' + '?log_in_from_id_token=' + tokens.tokens.idToken.value;
      });
    }
  });
</script>
