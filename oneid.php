<?php
/*
  Plugin Name: OneID Sign In
  Plugin URI: https://www.oneid.com
  Description: Login to Wordpress using OneID
  Version: 1.1.1
  Author: Dak Erwin and Glenn Welser
  Author URI: http://clarknikdelpowell.com
  License:

  Copyright 2012

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define("ONEID_UID", "oneid_uid"); // Usermeta field name

require_once plugin_dir_path(__FILE__) . 'oneid_api.php';

class OneID {

    /**
     * Initializes the plugin.
     */
    function __construct() {

        // Register login scripts.
        add_action('login_enqueue_scripts', array(&$this, 'oneid_register_login_scripts'));

        // Display login form.
        add_action('login_form', array(&$this, 'oneid_login_form'));

        // Create OneID settings page.
        add_action('admin_menu', array(&$this, 'oneid_options_menu'));

        // Update old WordPress user with new OneID info.
        add_action('wp_login', array(&$this, 'oneid_link'), 1, 2);

        // Connect/Disconnect a OneID uid with a WordPress account
        add_action('profile_personal_options', array(&$this, 'trigger_oneid_link'));

        // These don't work in a symlink
        register_activation_hook('/oneid-login/oneid.php', array(&$this, 'oneid_activate'));
        register_deactivation_hook('/oneid-login/oneid.php', array(&$this, 'oneid_deactivate'));
    }

// end constructor

    /**
     * Checks database for api key and id and fills both in if either is blank
     * If the oneid_opitions option isn't found, create the default one here
     */
    function oneid_activate() {
        if ($options = get_option('oneid_options')) {
            if ($options["oneid_api_id"] == '' || $options["oneid_api_key"] == ''){
                $values = _get_api_values();
                $options["oneid_api_id"] = $values["API_ID"];
                $options["oneid_api_key"] = $values["API_KEY"];
                update_option('oneid_options', $options);
            }
        }
        // Set default oneid_options values here
        else {
            $values = _get_api_values();
            $options = array( "oneid_api_id" => $values["API_ID"],
                              "oneid_api_key" => $values["API_KEY"],
                              "oneid_username" => "name",
                              "oneid_email_verify" => "on");
            add_option('oneid_options', $options);
        }
    }

    /**
     * Actions taken when plugin is deactivated.
     */
    function oneid_deactivate() {

    }

    /**
     * Update a verified user's account on email collision
     *
     * @param string $user_login -- unused username; it's only included so we can access
     *                              the second default parameter
     * @param array $user -- contains all the database values for a user
     */
    function oneid_link($user_login, $user) {
        if ($oneid_id = $_COOKIE['oneid_user']) {
            // Save the user's OneID UID value so we can find them next time
            update_user_meta($user->ID, ONEID_UID, $oneid_id);
        }
    }

    /**
     * If a user has linked their OneID to WP then offer an unlink button.
     * If there is no link detected then offer the user a button to connect the two accounts
     *
     * @param string $user_login -- dummy param to access the second default WP parameter
     * @param array $user -- contains all database values for a user
     */
    function trigger_oneid_link($user_login) {
        $wp_id = get_current_user_id();
        if ($wp_id != 0) {
            $oneid_uid = get_user_meta($wp_id, ONEID_UID, true);
            $table_text = ($oneid_uid) ? "Unlink" : "Link";
            // Visual HTML
            echo '
                <script type="text/javascript" src="https://api.oneid.com/js/includeexternal.js"></script>
                <h3>OneID</h3>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="'. ONEID_UID .'">OneID '. $table_text .'</label>
                            </th>
                            <td>';
            echo '<input type="button" class="button button-';
            echo ($table_text == "Unlink")?'delete':'primary';
            echo '" id="trigger_'. ONEID_UID .'" value="'. $table_text .' your OneID">
                            </td>
                        </tr>
                    </tbody>
                </table>';
            // Underlying script
            echo '
                <script>
                    document.getElementById("trigger_oneid_uid").onclick = function() {
                        OneID.signin({
                            "challenge": {
                                "attr": "email[email] name[first_name] name[last_name]",
                                "callback": "'. plugins_url("link.php", __FILE__) .'"
                            }
                        });
                    };
                </script>';
        }
    }

    /**
     * Register and enqueue login scripts.
     */
    function oneid_register_login_scripts() {
        wp_register_script('oneid', 'https://api.oneid.com/js/includeexternal.js', null, null, false);
        wp_enqueue_script('oneid');
    }

// end register_login_scripts

    /**
     * Create OneID options menu.
     */
    function oneid_options_menu() {
        add_options_page('OneID', 'OneID', 'manage_options', 'oneid', array($this, 'oneid_options_page'));

        // Register settings
        add_action('admin_init', array(&$this, 'register_oneid_options'));
    }

// end oneid_options_menu

    /**
     * Register OneID settings fields.
     */
    function register_oneid_options() {
        register_setting('oneid_options', 'oneid_options');
    }

// end register_oneid_options

    /**
     * Display OneID settings page in admin.
     */
    function oneid_options_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permission to access this page.'));
        }

        if ($options = get_option('oneid_options')) {
            //These three are guaranteed to be set when the plugin gets first activated
            $oneid_api_id = $options["oneid_api_id"];
            $oneid_api_key = $options["oneid_api_key"];
            $oneid_username = $options["oneid_username"];
            if(isset($options["oneid_email_verify"])) {
                $oneid_email_verify = $options["oneid_email_verify"];
            }
        }

        echo '
        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>
            <h2>OneID Settings</h2>
            <form action="options.php" method="post">
        ';
        settings_fields('oneid_options');
        do_settings_sections('oneid_options');
        echo '
            <p><label for="oneid_api_id">API ID</label><br />
                <input name="oneid_options[oneid_api_id]"
                       id="oneid_api_id"
                       value="' . $oneid_api_id . '" />
            </p>
            <p><label for="oneid_api_key">API Key</label><br />
                <input name="oneid_options[oneid_api_key]"
                       id="oneid_api_key"
                       value="' . $oneid_api_key . '" /></p>
            <p><label for="oneid_user_login">Username Format</label><br />
                <select name="oneid_options[oneid_username]" id="oneid_user_login">
                    <option value="name">FirstName_LastName</option>
                    <option value="email"';
        $foo = $oneid_username == "email" ? ' selected>' : '>';
        echo $foo;
        echo '
                        Email</option>
                </select></p>
            <p>
                <input type="checkbox"
                       name="oneid_options[oneid_email_verify]"
                       id="oneid_email_verify"';
        $bar = isset($oneid_email_verify) ? ' checked>' : '>';
        echo $bar;
        echo '
                    <label for="oneid_email_verify">&nbsp; Require Email Verification</label>
                </p>';
        submit_button(null, 'primary', 'submit', true);
        echo '
            </form>
        </div>';
    }

// end oneid_options_page

    /**
     * Display OneID Button on WP login form.
     */
    function oneid_login_form() {
        $redirect = '';

        if (array_key_exists("redirect_to", $_GET)) {
            $redirect .= "?redirect_to={$_GET['redirect_to']}";
        }

        echo '<hr style="clear: both; margin-bottom: 1.0em; border: 0; border-top: 1px solid #999; height: 1px;" />';

        //This is skipped when a user's email is already associated with a uid
        if ( !isset($_GET['exists']) || $_GET['exists'] != 'true' ) {

            echo '
                <p style="margin-bottom: 8px;">
                    <label style="display: block; margin-bottom: 5px;">Or login with <a href="http://www.oneid.com">OneID</a>
                </p>
                <p style="margin-bottom: 8px;">' . 
                    OneIDAPI::OneID_Button('email[email] name[first_name] name[last_name]',
                                           plugins_url('process.php', __FILE__) . $redirect) .
                '</p>';
        }
        else {
            echo "<p class='message'>We found another account with the same email. ".
                 "Please login using your WordPress username and password ".
                 "to complete your OneID link!</p>";
            echo '<br />';
        }
    }
}

new OneID();

/**
 * Gets an api id/key pair from keychain
 *
 * @return : json dictionary that contains an API instance credentials
 */
function _get_api_values() {
    $ch = curl_init('https://keychain.oneid.com/register');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($ch);
    curl_close($ch);
    $values = json_decode($json, true);
    return $values;
}
?>
