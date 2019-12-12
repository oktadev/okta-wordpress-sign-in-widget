<?php
namespace Okta;

class OktaAdmin{
    public $optionSetName = 'okta_options';
    public $fields = ['okta_base_url', 'okta_client_id', 'okta_client_secret', 'okta_auth_server_id'];

    public function __construct(){
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_menu', [$this, 'okta_admin_menu']);
    }

    public function admin_init(){
        register_setting('okta_admin', $this->optionSetName, [$this, 'validateOptions']);
    }

    public function validateOptions($input){
        $valid = [];
        $valid['okta_base_url'] = $input['okta_base_url'];
        $valid['okta_client_id'] = $input['okta_client_id'];
        $valid['okta_client_secret'] = $input['okta_client_secret'];
        $valid['okta_auth_server_id'] = $input['okta_auth_server_id'];
        return $valid;
    }

    public function okta_admin_menu(){
        add_menu_page('Okta Signin', 'Okta Signin', 'manage_options', 'okta_admin', [$this, 'okta_admin_page'], 'dashicons-groups');
    }

    public function okta_admin_page(){
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        $updated = false;
        $hidden = 'okta_save';
        if( isset($_POST[ $hidden ]) && $_POST[ $hidden ] == 'Y' ) {
            $updated = true;
            $values = [];
            foreach($this->fields as $field){
                $values[$field] = $_POST[$field];
            }
            update_option($this->optionSetName, $values);
        }
        ?>
            <div class="wrap">
                <h2> <?php echo __( 'Okta Signin', 'okta-admin' ) ?> </h2>
                <?php if($updated): ?>
                    <div class="updated"><p><strong><?php _e('Settings Saved.', 'okta-admin' ); ?></strong></p></div>
                <?php endif;?>
                <form method="post">
                    <?php
                        settings_fields( 'okta_options' );
                        $data = get_option($this->optionSetName);
                    ?>
                    <input type="hidden" name="<?php echo $hidden; ?>" value="Y" />
                    <table class="form-table">
                        <tbody>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="okta_base_url">Okta Base URL</label>
                                </th>
                                <td>
                                    <input name="okta_base_url" type="text" id="okta_base_url" placeholder="ex: https://youroktadomain.okta.com" value="<?php echo $data['okta_base_url']; ?>" />
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="okta_client_id">Okta Client ID</label>
                                </th>
                                <td>
                                    <input name="okta_client_id" type="text" id="okta_client_id" value="<?php echo $data['okta_client_id']; ?>" />
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="okta_client_secret">Okta Client Secret</label>
                                </th>
                                <td>
                                    <input name="okta_client_secret" type="password" id="okta_client_secret" value="<?php echo $data['okta_client_secret']; ?>" />
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="okta_auth_server_id">Okta Auth Server ID</label>
                                </th>
                                <td>
                                    <input name="okta_auth_server_id" type="text" placeholder="ex: default" id="okta_auth_server_id" value="<?php echo $data['okta_auth_server_id']; ?>" />
                                    <p class="description">If you're using API Access Management, define the auth server ID.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
                    </p>
                </form>
            </div>
        <?php
    }

}