<div class="wrap brute-force-login">
    <h2><?php _e('Ip List', 'brute-force-login-wordpress'); ?></h2>

    <h3><?php _e('Blocked IPs', 'brute-force-login-wordpress'); ?></h3>
    <table class="wp-list-table widefat fixed">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="30%"><?php _e('Address', 'brute-force-login-wordpress'); ?></th>
                <th width="65%"><?php _e('Actions', 'brute-force-login-wordpress'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            foreach ($this->getIPList('denylist') as $deniedIP):
                ?>
                <tr <?php echo ($i % 2 == 0) ? 'class="even"' : ''; ?>>
                    <td><?php echo $i; ?></td>
                    <td><strong><?php echo $deniedIP ?></strong></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="IP" value="<?php echo $deniedIP ?>" />
                            <input type="submit" name="removedenylist" value="<?php echo __('Unblock', 'brute-force-login-wordpress'); ?>" class="button" />
                        </form>
                    </td>
                </tr>
                <?php
                $i++;
            endforeach; 
            ?>
            <tr <?php echo ($i % 2 == 0) ? 'class="even"' : ''; ?>>
                <td><?php echo $i; ?></td>
        <form method="post" action="">
            <td>
                <input type="text" name="IP" placeholder="<?php _e('IP to block', 'brute-force-login-wordpress'); ?>" required />
            </td>
            <td>
                <input type="submit" name="denylist" value="<?php _e('Manually block IP', 'brute-force-login-wordpress'); ?>" class="button button-primary" />
            </td>
        </form>
        </tr>
        </tbody>
    </table>

    <h3><?php _e('Whitelisted IPs', 'brute-force-login-wordpress'); ?></h3>
    <table class="wp-list-table widefat fixed">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="30%"><?php _e('Address', 'brute-force-login-wordpress'); ?></th>
                <th width="65%"><?php _e('Actions', 'brute-force-login-wordpress'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            $whitelist = $this->getIPList('whitelist');
            foreach ($whitelist as $whitelistedIP):
                ?>
                <tr <?php echo ($i % 2 == 0) ? 'class="even"' : ''; ?>>
                    <td><?php echo $i; ?></td>
                    <td><strong><?php echo $whitelistedIP ?></strong></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="IP" value="<?php echo $whitelistedIP ?>" />
                            <input type="submit" name="removewhitelist" value="<?php echo __('Remove from whitelist', 'brute-force-login-wordpress'); ?>" class="button" />
                        </form>
                    </td>
                </tr>
                <?php
                $i++;
            endforeach; 
            ?>
            <tr <?php echo ($i % 2 == 0) ? 'class="even"' : ''; ?>>
                <td><?php echo $i; ?></td>
        <form method="post" action="">
            <td>
                <input type="text" name="IP" placeholder="<?php _e('IP to whitelist', 'brute-force-login-wordpress'); ?>" required />
            </td>
            <td>
                <input type="submit" name="whitelist" value="<?php _e('Add to whitelist', 'brute-force-login-wordpress'); ?>" class="button button-primary" />
            </td>
        </form>
        </tr>
        </tbody>
    </table>

    <form id="reset_form" method="post" action="">
        <input type="hidden" name="reset" value="true" />
    </form>

    <form id="whitelist_current_ip_form" method="post" action="">
        <input type="hidden" name="whitelist" value="true" />
        <input type="hidden" name="IP" value="<?php echo $currentIP; ?>" />
    </form>
</div>