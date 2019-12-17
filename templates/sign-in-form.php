<!-- load the Okta sign-in widget-->
<script src="https://global.oktacdn.com/okta-signin-widget/3.2.0/js/okta-sign-in.min.js" type="text/javascript"></script>
<link href="https://global.oktacdn.com/okta-signin-widget/3.2.0/css/okta-sign-in.min.css" type="text/css" rel="stylesheet"/>

<style type="text/css">
    #wordpress-login{
        text-align: center;
    }

    #wordpress-login a{
        font-size:10px;
        color: #999;
        text-decoration:none;
        font-family: montserrat,Arial,Helvetica,sans-serif;
    }
</style>

<?php
    $options = defined('OKTA_OPTIONS') ? OKTA_OPTIONS : Okta\OktaSignIn::$OktaTemplateDefaults;
?>

<div id="primary" class="content-area">  
  <div id="widget-container"></div>
  <?php if($options['okta_wordpress_login']): ?>
    <div id="wordpress-login"><a href="<?php echo Okta\OktaSignIn::getWordpressLoginUrl(); ?>">Login via Wordpress</a></div>
  <?php endif; ?>
</div>

<script>
    var oktaSignIn = new OktaSignIn({
        baseUrl: '<?php echo $options['okta_base_url'] ?>',
        clientId: '<?php echo $options['okta_client_id'] ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        authParams: {
            issuer: '<?php echo !empty($options['okta_auth_server_id']) ? ($options['okta_base_url'] . '/oauth2/' . $options['okta_auth_server_id']) : $options['okta_base_url'] ?>',
            responseType: 'code',
            display: 'page',
            state: '<?php echo Okta\OktaSignIn::generateState() ?>'
        }
    });
    oktaSignIn.renderEl(
        { el: '#widget-container' },
        function success(res) { }
    );
</script>
