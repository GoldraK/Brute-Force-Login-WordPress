<div class="wrap brute-force-login">
    <h2><?php _e('Brute Force Login Settings', 'brute-force-login-wordpress'); ?></h2>
    <?php settings_errors(); ?>

    <div class="metabox-holder">

        <div class="postbox">
            <h3><?php _e('Options', 'brute-force-login-wordpress'); ?></h3>
            <form method="post" action="options.php"> 
                <?php settings_fields('brute-force-login-wordpress'); ?>
                <div class="inside">
                    <p><strong><?php _e('Allowed login attempts before blocking IP', 'brute-force-login-wordpress'); ?></strong></p>
                    <p><input type="number" min="1" max="100" name="bflwp_allowed_attempts" value="<?php echo $this->options['allowed_attempts']; ?>" /></p>

                    <p><strong><?php _e('Maximum time of attempts', 'brute-force-login-wordpress'); ?></strong></p>
                    <p><input type="number" min="1" name="bflwp_max_time" value="<?php echo $this->options['max_time']; ?>" /></p>

                </div>
                <div class="postbox-footer">
                    <?php submit_button(__('Save', 'brute-force-login-wordpress'), 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
    </div>

    <form id="reset_form" method="post" action="">
        <input type="hidden" name="reset" value="true" />
    </form>

</div>