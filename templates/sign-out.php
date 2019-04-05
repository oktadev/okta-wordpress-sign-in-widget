<?php
/**
 * What does this do?
 */
?>
<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/1.13.0/okta-auth-js.min.js" type="text/javascript"></script>
<script>
	var authClient = new OktaAuth({
		url: '<?= (get_option('okta-base-url')) ?>',
		clientId: '<?= (get_option('okta-client-id')) ?>',
		issuer: '<?= (get_option('okta-base-url')) ?>/oauth2/<?= (get_option('okta-auth-server-id')) ?>',
	});

	authClient.signOut()
		.then(function() {
			window.location = '<?= (home_url()) ?>';
		});
</script>