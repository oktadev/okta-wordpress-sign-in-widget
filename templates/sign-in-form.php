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
    $default = [
        'OKTA_BASE_URL' => '',
        'OKTA_CLIENT_ID' => '',
        'OKTA_AUTH_SERVER_ID' => ''
    ];
    $options = defined('OKTA_OPTIONS') ? OKTA_OPTIONS : $default;
?>

<div id="primary" class="content-area">  
  <div id="widget-container"></div>
  <div id="wordpress-login"><a href="<?php echo Okta\OktaSignIn::getWordpressLoginUrl(); ?>">Login via Wordpress</a></div>
</div>

<script>
    var oktaSignIn = new OktaSignIn({
        baseUrl: '<?php echo $options['OKTA_BASE_URL'] ?>',
        clientId: '<?php echo $options['OKTA_CLIENT_ID'] ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        authParams: {
            issuer: '<?php echo !empty($options['OKTA_AUTH_SERVER_ID']) ? ($options['OKTA_BASE_URL'] . '/oauth2/' . $options['OKTA_AUTH_SERVER_ID']) : $options['OKTA_BASE_URL'] ?>',
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
