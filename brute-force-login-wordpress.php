<?php

/*
 * Plugin Name: Brute Force Login WordPress
 * Plugin URI: https://github.com/GoldraK/Brute-Force-Login-WordPress
 * Description: Protect WordPress against brute force attacks
 * Author: Rafael Otal
 * Author URI: http://rotasim.com/
 * Version: 0.1
 * License: GPL3
 * Text Domain: brute-force-login-wordpress
 */


if ( ! defined( 'ABSPATH' ) ) {
  die;
}

if ( !class_exists( 'BruteForceLoginWordPress' ) ) {
  class BruteForceLoginWordPress {

    private $table;
    private $options;

    /**
     * Initializes $table
     * Load Hooks
     * Interacts with WordPress hooks.
     * 
     * @return void
     */
    function __construct(){

      $this->table = $GLOBALS['wpdb']->prefix . 'bflwp_logs';

      register_activation_hook(__FILE__, array($this, 'bflwp_install'));

      add_action( 'plugins_loaded', array( $this, 'bflwp_block' ) );
      add_action('admin_init', array($this, 'adminInit'));
      add_action('admin_menu', array($this, 'menuInit'));
      add_action('wp_login_failed', array($this, 'loginFailed'));
      add_action( 'init', array($this, 'Init') );
      
    }

    /**
     * Create Table bflwp_logs and config default settings
     *
     * @return void
     * 
     */
    public function bflwp_install() {

      global $wpdb;


      $sql = "CREATE TABLE $this->table (
      id int NOT NULL AUTO_INCREMENT,
      username varchar(255) NOT NULL DEFAULT '',
      time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      ip varchar(255) NOT NULL DEFAULT '',
      PRIMARY KEY (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('bflwp_allowed_attempts',5, '', 'no');
    add_option('bflwp_max_time', 5, '', 'no');
    add_option('bflwp_whitelist',array(), '', 'no');
    add_option('bflwp_denylist',array(), '', 'no');
  }


    /**
     * Basic admin init
     * 
     * @return void
     */
    public function adminInit() {
      $this->registerOptions();
    }

    /**
     * Create menu dashboard
     * 
     * @return void
     * 
     */
    public function menuInit() {
        //Add settings page to the Settings menu
      add_menu_page(sprintf(__('Brute Force Login Settings', 'brute-force-login-wordpress')), 'Brute Force Login', 'manage_options', 'bf-login-wp', array($this, 'showSettingsPage'));
      add_submenu_page('bf-login-wp', sprintf(__('Brute Force Login Settings', 'brute-force-login-wordpress')), 'General', 'manage_options', 'bf-login-wp', array($this, 'showSettingsPage'));
      add_submenu_page('bf-login-wp', sprintf(__('Ip List', 'brute-force-login-wordpress')), sprintf(__('Ip List', 'brute-force-login-wordpress')), 'manage_options', 'bf-login-wp-iplist', array($this, 'showIpListPage'));
    }

    /**
     * Init textdoamin to i18n
     */
    public function Init(){
      load_plugin_textdomain('brute-force-login-wordpress', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Block access deny ips
     * 
     * @return void
     */
    public function bflwp_block(){
      $ip = $this->getIp();
      
      $denylist = $this->getIPList('denylist');

      if(in_array($ip, $denylist)){
        header('HTTP/1.0 403 Forbidden');
        die('No podras pasar');
      }

      foreach ($denylist as $value) {
        if($this->ip_in_range($ip,$value)){
          header('HTTP/1.0 403 Forbidden');
          die('No podras pasar');
        }
      }
    }

    /**
     * Registers options (settings).
     * 
     * @return void
     */
    private function registerOptions() {
      register_setting('brute-force-login-wordpress', 'bflwp_allowed_attempts', array($this, 'validateAllowedAttempts'));
      register_setting('brute-force-login-wordpress', 'bflwp_max_time', array($this, 'validateMaxTime'));

    }

    /**
     * Validates bflwp_allowed_attempts field.
     * 
     * @param mixed $input
     * @return int
     */
    public function validateAllowedAttempts($input) {
      if (is_numeric($input) && ($input >= 1 && $input <= 100)) {
        return $input;
      } else {
        add_settings_error('bflwp_allowed_attempts', 'bflwp_allowed_attempts', __('Allowed login attempts must be a number (between 1 and 100)', 'brute-force-login-wordpress'));
        $this->fillOption('allowed_attempts');
        return $this->options['allowed_attempts'];
      }
    }

    /**
     * Validates bflwp_max_time field.
     * 
     * @param mixed $input
     * @return int
     */
    public function validateMaxTime($input) {
      if (is_numeric($input) && $input >= 1) {
        return $input;
      } else {
        add_settings_error('bflwp_max_time', 'bflwp_max_time', __('Minutes max attempts must be a number (higher than 1)', 'brute-force-login-wordpress'));
        $this->fillOption('max_time');
        return $this->options['max_time'];
      }
    }

    /**
     * Fills options with value (from database).
     * 
     * @return void
     */
    private function fillOptions() {
      $this->options['allowed_attempts'] = get_option('bflwp_allowed_attempts', $this->options['allowed_attempts']);
      $this->options['max_time'] = get_option('bflwp_max_time', $this->options['max_time']);

    }

    /**
     * Fills single option with value (from database).
     * 
     * @param string $name
     * @return void
     */
    private function fillOption($name) {
      $this->options[$name] = get_option('bflwp_' . $name, $this->options[$name]);
    }


    /**
     * Shows settings page and handles user actions.
     * 
     * @return void
     */
    public function showSettingsPage() {

      $this->fillOptions();
      include 'admin/settings-page.php';
    }

    /**
     * Shows Ip List page and handles user actions.
     * 
     * @return void
     */
    public function showIpListPage() {
      if (isset($_POST['IP'])) {
        $IP = $_POST['IP'];
        if (isset($_POST['whitelist'])) { //Add IP to whitelist
          if ($this->addIPList($IP,'whitelist')) {
            $this->showMessage(sprintf(__('IP %s added to whitelist', 'brute-force-login-wordpress'), $IP));
          } else {
            $this->showError(sprintf(__('An error occurred while adding IP %s to whitelist', 'brute-force-login-wordpress'), $IP));
          }
        } elseif (isset($_POST['removewhitelist'])) { //Remove IP from whitelist
          if ($this->removeIPlist($IP,'whitelist')) {
            $this->showMessage(sprintf(__('IP %s removed from whitelist', 'brute-force-login-wordpress'), $IP));
          } else {
            $this->showError(sprintf(__('An error occurred while removing IP %s from whitelist', 'brute-force-login-wordpress'), $IP));
          }
        } elseif (isset($_POST['denylist'])) { //Add IP from denylist
          if ($this->addIPList($IP,'denylist')) {
            $this->showMessage(sprintf(__('IP %s added to denylist', 'brute-force-login-wordpress'), $IP));
          } else {
            $this->showError(sprintf(__('An error occurred while adding IP %s from denylist', 'brute-force-login-wordpress'), $IP));
          }
        } elseif (isset($_POST['removedenylist'])) { //Remove IP from denylist
          if ($this->removeIPlist($IP,'denylist')) {
            $this->showMessage(sprintf(__('IP %s removed from whitelist', 'brute-force-login-wordpress'), $IP));
          } else {
            $this->showError(sprintf(__('An error occurred while removing IP %s from whitelist', 'brute-force-login-wordpress'), $IP));
          }
        }
      }
      include 'admin/ip-list.php';
    }

    /**
     * Echoes message with class 'updated'.
     * 
     * @param string $message
     * @return void
     */
    private function showMessage($message) {
      echo '<div class="updated"><p>' . esc_html($message) . '</p></div>';
    }


    /**
     * Echoes message with class 'error'.
     * 
     * @param string $message
     * @return void
     */
    private function showError($message) {
      echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Called when a user login has failed
     * Increase number of attempts for clients IP. Deny IP if max attempts is reached.
     * 
     * @return void
     */
    public function loginFailed($username) {

      global $wpdb;

      $ip = $this->getIp();
      $this->fillOption('max_time');      
      $this->fillOption('allowed_attempts');

      
      $whitelist = $this->getIPList('whitelist');

      if (in_array($ip, $whitelist)) {
        return true;
      }

      foreach ($whitelist as $value) {
        if($this->ip_in_range($ip,$value)){
          return true;
        }
      }

      $wpdb->insert( $this->table, array( 'username' => $username, 'ip' => $ip ) );


      $count_to_block = $wpdb->get_var( $wpdb->prepare( 
        "
        SELECT count(*)
        FROM $this->table 
        WHERE ip = %s
        AND  time >= NOW() - INTERVAL %s MINUTE
        ", 
        $ip,
        $this->options['max_time']
      ) );


      if($count_to_block >= $this->options['allowed_attempts']){
        $this->addIPList($ip,'denylist');
      }
    }

    /**
     * Add Ip to list
     * @param integer $IP   Ip to add a list
     * @param string $type Type of list
     *
     * @return bool
     */
    private function addIPList($IP,$type) {
      if(!$this->validateCidr($IP)){
        if (!filter_var($IP, FILTER_VALIDATE_IP)) {
          return false;
        }
      }

      $iplist = get_option('bflwp_'.$type);

      if($type == 'whitelist'){
        $otheriplist = get_option('bflwp_denylist');
      }else{
        $otheriplist = get_option('bflwp_whitelist');
      }

      if (in_array($IP, $otheriplist)) {
        return false;
      }

      if (!is_array($iplist)) {
        $iplist = array($IP);
        return add_option('bflwp_'.$type, $iplist, '', 'no');
      }

      $iplist[] = $IP;

      return update_option('bflwp_'.$type, array_unique($iplist));
    }


    /**
     * Remove Ip to list
     * @param integer $IP   Ip to add a list
     * @param string $type Type of list
     *
     * @return bool
     */
    private function removeIPlist($IP,$type) {
      if(!$this->validateCidr($IP)){
        if (!filter_var($IP, FILTER_VALIDATE_IP)) {
          return false;
        }
      }

      $iplist = get_option('bflwp_'.$type);
      if (!is_array($iplist)) {
        return false;
      }

      if (($key = array_search($IP, $iplist)) !== false) {
        unset($iplist[$key]);
      }else{
        return false;
      }

      return update_option('bflwp_'.$type, $iplist);
    }

    /**
     * get Ip list
     * @param integer $IP   Ip to add a list
     * @param string $type Type of list
     *
     * @return bool
     */
    private function getIPList($type) {
      $iplist = get_option('bflwp_'.$type);

      if (!is_array($iplist)) {
        return array();
      }

      return $iplist;
    }


    /**
     * Get User IP
     * @return string User IP
     */
    private function getIp(){
      $ipaddress = '';
      if ($_SERVER['HTTP_CLIENT_IP'])
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
      else if($_SERVER['HTTP_X_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
      else if($_SERVER['HTTP_X_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
      else if($_SERVER['HTTP_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
      else if($_SERVER['HTTP_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
      else if($_SERVER['REMOTE_ADDR'])
        $ipaddress = $_SERVER['REMOTE_ADDR'];
      else
        $ipaddress = 'UNKNOWN';

      return $ipaddress;
    }


    /**
    * Check if a given ip is in a network.
    *
    * @see https://gist.github.com/ryanwinchester/578c5b50647df3541794
    *
    * @param  string $ip     IP to check in IPV4 format eg. 127.0.0.1
    * @param  string $range  IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
    * @return bool           true if the ip is in this range / false if not.
    */
    private function ip_in_range($ip, $range)
    {
      if (strpos($range, '/') == false) {
        $range .= '/32';
      }
        // $range is in IP/CIDR format eg 127.0.0.1/24
      list($range, $netmask) = explode('/', $range, 2);
      $ip_decimal = ip2long($ip);
      $range_decimal = ip2long($range);
      $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
      $netmask_decimal = ~ $wildcard_decimal;
      return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }

    /**
     *
     * @see  https://gist.github.com/mdjekic/ac1f264e37bddfc63be8a042ced52e64
     * 
     * Validates the format of a CIDR notation string
     *
     * @param string $cidr
     * @return bool
     */
    private function validateCidr($cidr)
    {
      $parts = explode('/', $cidr);
      if(count($parts) != 2) {
        return false;
      }
      $ip = $parts[0];
      $netmask = intval($parts[1]);
      if($netmask < 0) {
        return false;
      }
      if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $netmask <= 32;
      }
      if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $netmask <= 128;
      }

      return false;
    }

  }

  new BruteForceLoginWordPress();

}

?>
