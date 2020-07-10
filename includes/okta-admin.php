<?php
namespace Okta;

class OktaAdmin{

    public function __construct(){
        // https://codex.wordpress.org/Creating_Options_Pages
        add_action('admin_init', array($this, 'registerSettingsAction'));

        // https://codex.wordpress.org/Adding_Administration_Menus
        add_action('admin_menu', array($this, 'optionsMenuAction'));
    }

    public function registerSettingsAction() {
        add_settings_section(
            'okta-sign-in-widget-options-section',
            '',
            null,
            'okta-sign-in-widget'
        );

        register_setting('okta-sign-in-widget', 'okta-issuer-url', array(
            'type' => 'string',
            'show_in_rest' => false,
        ));
        add_settings_field(
            'okta-issuer-url',
            'Okta Issuer URI',
            function() { $this->optionsPageTextInputAction('okta-issuer-url', 'text', 'e.g. https://youroktadomain.okta.com/oauth2/default', 'Find your Issuer URI in the Admin console under <b>Security -> API</b>, or in the Developer console under <b>API -> Authorization Servers</b>'); },
            'okta-sign-in-widget',
            'okta-sign-in-widget-options-section'
        );

        register_setting('okta-sign-in-widget', 'okta-widget-client-id', array(
            'type' => 'string',
            'show_in_rest' => false,
        ));
        add_settings_field(
            'okta-widget-client-id',
            'Sign-In Widget Client ID',
            function() { $this->optionsPageTextInputAction('okta-widget-client-id', 'text', null, 'Register a "SPA" app in Okta and provide its Client ID here. Set the Login redirect URI in Okta to <code>'.wp_login_url().'</code>, and set the Logout redirect URI to <code>'.home_url().'</code>'); },
            'okta-sign-in-widget',
            'okta-sign-in-widget-options-section'
        );

        register_setting('okta-sign-in-widget', 'okta-allow-wordpress-login', array(
            'type' => 'boolean',
            'show_in_rest' => false,
        ));
        add_settings_field(
            'okta-allow-wordpress-login',
            'Allow Native WordPress Login',
            function() { $this->optionsPageCheckboxInputAction('okta-allow-wordpress-login', 'checkbox', 'Check this to allow local WordPress users to log in with a password. When unchecked, Okta will be the only way users can log in. Make sure you have a WordPress admin user with an email address matching an Okta user already.'); },
            'okta-sign-in-widget',
            'okta-sign-in-widget-options-section'
        );
    }

    public function optionsMenuAction() {
        add_options_page(
            'Okta Sign-In Widget Options',
            'Okta Sign-In Widget',
            'manage_options',
            'okta-sign-in-widget',
            array($this, 'optionsPageAction')
        );
    }

    public function optionsPageAction() {
        if (current_user_can('manage_options'))  {
            include(plugin_dir_path(__FILE__)."../templates/options-form.php");
        } else {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }
    }

    public function optionsPageTextInputAction($option_name, $type, $placeholder=false, $description=false) {
        $option_value = get_option($option_name, '');
        printf(
            '<input type="%s" id="%s" name="%s" value="%s" style="width: 100%%" autocomplete="off" placeholder="%s" />',
            esc_attr($type),
            esc_attr($option_name),
            esc_attr($option_name),
            esc_attr($option_value),
            esc_attr($placeholder)
        );
        if($description)
            echo '<p class="description">'.$description.'</p>';
    }

    public function optionsPageCheckboxInputAction($option_name, $type, $description=false) {
        $option_value = get_option($option_name, false);
        printf(
            '<input type="%s" id="%s" name="%s" value="1" %s>',
            esc_attr($type),
            esc_attr($option_name),
            esc_attr($option_name),
            $option_value ? 'checked="checked"' : ''
        );
        if($description) 
            echo '<p class="description">'.$description.'</p>';
    }

}
