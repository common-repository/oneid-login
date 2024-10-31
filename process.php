<?php

/*
 * Copyright 2012  by OneID
 *
 * This page is called by OneID and logs the user in.
 */

include_once oneid_wpload_path();
require_once plugin_dir_path(__FILE__) . '/oneid_api.php';

/*
 * Call OneID's server to validate the user
 */
$attrs = OneIDAPI::OneID_Response();

/*
 * If the user is validated, we log the user into Wordpress
 */
if ($attrs) {

    global $wp_user, $user_login_id;

    // Get user info from OneID response
    $oneid_user_id = $attrs["uid"];
    $oneid_email = $attrs["attr"]["email"]["email"];
    $oneid_first_name = $attrs["attr"]["name"]["first_name"];
    $oneid_last_name = $attrs["attr"]["name"]["last_name"];

    // Check whether email was verified if the option was set
    // This occurs for everyone at each login attempt
    $options = get_option('oneid_options');
    if ( isset($options) && isset($options['oneid_email_verify']) ) {
        if ($options['oneid_email_verify'] == "on") {
	    if(!isset($attrs['attr_claim_tokens']['email'])){
	        echo json_encode(array("error" =>"Please verify your email address."));
	        exit();
            }
        }
    }

    // Check to see if there is a Wordpress user attached to the OneID UID
    if (!isset($wp_user)) {
        //This returns a unique set of users (usually only one), based on the metadata given
        $wp_args = array('meta_key' => ONEID_UID, 'meta_value' => $oneid_user_id);
        if ($wp_user = get_users($wp_args)) {
            // We found a user for this OneID UID
            // Now grab some user data
            $user_data = get_user_by('id', $wp_user[0]->ID);
            $user_login_id = $wp_user[0]->ID;
            $user_login_name = $user_data->user_login;
        }
    }

    // If we didn't find a user, lookup by email address
    if (!$user_login_id) {

        if ($wp_user = get_user_by('email', $oneid_email)) {

            // We found a user by email address
            // Now grab some user data
            $user_data = get_user_by('id', $wp_user->ID);
            $user_login_id = $wp_user->ID;
            $user_login_name = $user_data->user_login;

            // Store their UID into a cookie.
            // All five parameters are (mysteriously) necessary for wordpress
            setcookie("oneid_user", $oneid_user_id, time() + 3600, "/", '.' . $_SERVER["SERVER_NAME"]);

            echo OneIDAPI::OneID_Redirect(wp_login_url() . "?exists=true");
            exit();
        }
    }

    // If we didn't find a user, the user has never logged in before
    if (!$user_login_id) {
        // Let's create a new user if the admin allows it
        if(get_option('users_can_register') == 1) {

            // Build user data using OneID response
            $user_data = array();

            // Generate username using Setting > OneID > Username
            if ($options = get_option('oneid_options')) {
                $username = $options['oneid_username'];
                if ($username == "email") {
                    $user_data['user_login'] = $oneid_email;
                }
                //Default
                else {
                    if (strlen($oneid_first_name) + strlen($oneid_last_name) != 0)
                        $user_data['user_login'] = $oneid_first_name . '_' . $oneid_last_name;
                    else
                        $user_data['user_login'] = $oneid_email;
                }
            }
            // Generate a random password
            $user_data['user_pass'] = wp_generate_password();
            // Record user's name
            $user_data['user_nicename'] = $oneid_first_name . ' ' . $oneid_last_name;
            $user_data['first_name'] = $oneid_first_name;
            $user_data['last_name'] = $oneid_last_name;
            $user_data['display_name'] = $oneid_first_name . ' ' . substr($oneid_last_name, 0, 1);
            // Record user's email address
            $user_data['user_email'] = $oneid_email;
            // Assign new user's role to the value in Setting > General > New User Default Role
            if ($options = get_option('default_role')) {
                $default_role = trim($options);
                $user_data['role'] = $default_role;
            }

            // Insert the user into the database
            $user_login_id = wp_insert_user($user_data);
            $user_login_name = $user_data['user_login'];
            // Send new user notification
            wp_new_user_notification($user_login_name);

            // Save the user's OneID UID value so we can find them next time
            update_user_meta($user_login_id, ONEID_UID, $oneid_user_id);
        }
        else {
            echo json_encode(array("error" =>"You cannot register for a new WordPress account. " .
                    "Please contact your site administrator if you feel that you have reached this message in error."));
            exit();
        }
    }

    if ($user_login_id) {
        // Log the user into Wordpress
        wp_set_auth_cookie($user_login_id, false);
    }

    // Get the redirect URL
    if (array_key_exists("redirect_to", $_GET)) {
        $redirectTo = $_GET['redirect_to'];
    }
    else {
        $redirectTo = admin_url();
    }
    echo OneIDAPI::OneID_Redirect($redirectTo);
}

/**
 * Finds the wp-load.php file, which is located in the wordpress root directory
 * If this file is not stored two or 3 levels down (i.e. in the plugins/ folder
 * or in the plugins/oneid_login/ folder), we return false;
 *
 * @return string
 */
function oneid_wpload_path() {
    $base = dirname($_SERVER["SCRIPT_FILENAME"]);
    $path = false;
 
    while ($base != '/') {
        if (@file_exists($base."/wp-load.php")) {
           $path = $base."/wp-load.php";
           break;
        } else {
           $base = dirname($base);
        }
    }
 
    if ($path != false) {
        $path = str_replace("\\", "/", $path);
    }
    return $path;
}
?>
