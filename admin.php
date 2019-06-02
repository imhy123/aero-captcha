<?php
if (!is_admin()) {
    die();
}
?>

<div class="wrap">
<h2><?php _e('Aero Catpcha Options','aero-captcha'); ?></h2>
<form method="post" action="options.php">
    <?php
    echo settings_fields( 'aero_captcha' );
    ?>
    <p><?php echo sprintf(__('<a href="%s" target="_blank">Click here</a> to create or view keys for Google NoCaptcha.','aero-captcha'),'https://www.google.com/recaptcha/admin#list'); ?></p>
    <table class="form-table form-v2">
        <tr valign="top">
                <th scope="row"><label for="id_aero_captcha_key"><?php _e('Site Key','aero-captcha'); ?> (v2): </span>
                </label></th>
            <td><input type="text" id="id_aero_captcha_key" name="aero_captcha_key" value="<?php echo get_option('aero_captcha_key'); ?>" size="40" /></td>
        </tr>
        <tr valign="top">
                <th scope="row"><label for="id_aero_captcha_secret"><?php _e('Secret Key','aero-captcha'); ?> (v2): </span>
                </label></th>
            <td><input type="text" id="id_aero_captcha_secret" name="aero_captcha_secret" value="<?php echo get_option('aero_captcha_secret'); ?>" size="40" /></td>
        </tr>
        <tr valign="top">
                <th scope="row"><label for="id_aero_captcha_whitelist"><?php _e('Whitelist IP ( 1 per line )','aero-captcha'); ?> (v2): </span>
                </label></th>
            <td><textarea type="text" id="id_aero_captcha_whitelist" name="aero_captcha_whitelist" cols="39" rows="5"><?php echo get_option('aero_captcha_whitelist'); ?></textarea></td>
        </tr>
    </table>
    <p>
    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes','aero-captcha'); ?>">
    <button name="reset" id="reset" class="button">
        <?php _e('Delete Keys and Disable','aero-captcha'); ?>
    </button>
    </p>
</form>
</div>

<script>
(function($) {
    $('#reset').on('click', function(e) {
        e.preventDefault();
        $('#id_aero_captcha_key').val('');
        $('#id_aero_captcha_secret').val('');
        $('#id_aero_captcha_whitelist').val('');
        $('#submit').trigger('click');
    });
})(jQuery);
</script>
<style>
    #submit + #reset {
        margin-left: 1em;
    }
</style>
