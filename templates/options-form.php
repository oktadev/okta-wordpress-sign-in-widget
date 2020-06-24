<div class="wrap">
	<h1>Okta Sign-In Widget</h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'okta-sign-in-widget' ); ?>
		<?php do_settings_sections( 'okta-sign-in-widget' ); ?>
		<?php submit_button(); ?>
	</form>
</div>
