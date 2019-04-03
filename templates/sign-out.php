<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/1.13.0/okta-auth-js.min.js" type="text/javascript"></script>
<script>
	var authClient = new OktaAuth({
		url: '<?php echo get_option('okta-base-url'); ?>',
		clientId: '<?php echo get_option('okta-client-id'); ?>',
		issuer: '<?php echo get_option('okta-base-url') ?>/oauth2/<?php echo get_option('okta-auth-server-id'); ?>',
	});
	authClient.signOut()
		.then(function() {
			window.location = '<?php echo home_url(); ?>';
		});
</script>