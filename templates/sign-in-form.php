<?php include plugin_dir_path(__FILE__).'/../includes/widget.php'; ?>

<style type="text/css">
    body {
        font-family: montserrat,Arial,Helvetica,sans-serif;        
    }

    #wordpress-login{
        text-align: center;
    }

    #wordpress-login a{
        font-size:10px;
        color: #999;
        text-decoration:none;
    }

    #error {
        max-width: 500px;
        margin: 20px auto;
        padding: 20px;
        border: 1px #d93934 solid;
        border-radius: 6px;
    }
    #error h2 {
        color: #d93934;
    }
</style>

<?php if(isset($_GET['error'])): ?>
<div id="error">
    <h2>Error: <?php echo htmlspecialchars($_GET['error']) ?></h2>
    <p><?php echo htmlspecialchars($_GET['error_description']) ?></p>
</div>
<?php endif ?>

<div id="primary" class="content-area">
  <div id="okta-login-container"></div>
  <?php if(get_option('okta-allow-wordpress-login')): ?>
      <div id="wordpress-login"><a href="<?php echo wp_login_url(); ?>?wordpress_login=true">Login via Wordpress</a></div>
  <?php endif ?>
</div>

<script>
    var oktaSignIn = new OktaSignIn({
        baseUrl: '<?php echo parse_url($issuer = get_option('okta-issuer-url'), PHP_URL_SCHEME).'://'.parse_url($issuer, PHP_URL_HOST) ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        clientId: '<?php echo get_option('okta-widget-client-id') ?>',
        scopes: '<?php echo apply_filters( 'okta_widget_token_scope', 'openid email') ?>'.split(' '),
        authParams: {
            display: 'page',
            issuer: '<?php echo get_option('okta-issuer-url') ?>'
        }
    });

    oktaSignIn.authClient.token.getUserInfo().then(function(user) {
      console.log("Already logged in");
      oktaSignIn.authClient.tokenManager.get('idToken').then(function(idToken){
        window.location = '<?php echo wp_login_url() ?>?log_in_from_id_token='+idToken.value;
      });
    }, function(error) {
      oktaSignIn.showSignInToGetTokens({
        el: '#okta-login-container'
      }).then(function(tokens) {
        oktaSignIn.authClient.tokenManager.setTokens(tokens);
        oktaSignIn.remove();

        const idToken = tokens.idToken;
        window.location = '<?php echo wp_login_url() ?>?log_in_from_id_token='+idToken.value;

      }).catch(function(err) {
        console.error(err);
      });
    });
</script>
