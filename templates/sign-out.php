<script src="https://global.oktacdn.com/okta-signin-widget/4.1.3/js/okta-sign-in.min.js" type="text/javascript"></script>
<script>
    var signIn = new OktaSignIn({
        baseUrl: '<?php echo OKTA_BASE_URL ?>',
        authParams: {
        	clientId: '<?php echo OKTA_WIDGET_CLIENT_ID ?>',
            display: 'page',
            issuer: '<?php echo defined('OKTA_AUTH_SERVER_ID') ? (OKTA_BASE_URL . '/oauth2/' . OKTA_AUTH_SERVER_ID) : OKTA_BASE_URL ?>'
        }
    });

    signIn.authClient.tokenManager.get('id_token')
    .then(function(token){
    	console.log(token);
    	console.log(token.idToken);
		signIn.authClient.signOut({
			idToken: token,
			postLogoutRedirectUri: '<?php echo home_url() ?>'
		});
    });
</script>
