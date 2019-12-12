<!-- load the Okta sign-in widget-->
<script src="https://global.oktacdn.com/okta-signin-widget/3.2.0/js/okta-sign-in.min.js" type="text/javascript"></script>
<link href="https://global.oktacdn.com/okta-signin-widget/3.2.0/css/okta-sign-in.min.css" type="text/css" rel="stylesheet"/>

<div id="primary" class="content-area">
  <div id="widget-container"></div>
</div>

<script>
    var oktaSignIn = new OktaSignIn({
        baseUrl: '<?php echo OKTA_OPTIONS['OKTA_BASE_URL'] ?>',
        clientId: '<?php echo OKTA_OPTIONS['OKTA_CLIENT_ID'] ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        authParams: {
            issuer: '<?php echo !empty(OKTA_OPTIONS['OKTA_AUTH_SERVER_ID']) ? (OKTA_OPTIONS['OKTA_BASE_URL'] . '/oauth2/' . OKTA_OPTIONS['OKTA_AUTH_SERVER_ID']) : OKTA_OPTIONS['OKTA_BASE_URL'] ?>',
            responseType: 'code',
            display: 'page',
            state: '<?php echo Okta\OktaSignIn::generateState() ?>'
        }
    });
    oktaSignIn.renderEl(
        { el: '#widget-container' },
        function success(res) { console.log('hi'); }
    );
</script>
