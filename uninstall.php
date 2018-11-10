<?php
// if we're not uninstalling..
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

$options = array('bflwp_whitelist','bflwp_denylist','bflwp_allowed_attempts','bflwp_reset_time');
 
 foreach ($options as $option) {
 	delete_option($option);
 }

// drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bflwp_logs");