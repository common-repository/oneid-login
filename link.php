<?php

/**
 * Copyright 2013 by OneID
 *
 * This page is called by OneID from a user's profile page.
 * It will link or unlink a user's WordPress account from their OneID account.
 */

include_once oneid_wpload_path();
require_once plugin_dir_path(__FILE__) . '/oneid_api.php';

// Validate the OneID user
$attrs = OneIDAPI::OneID_Response();

if ($attrs) {

    // This will only show up on current user's profile,
    // so the admin won't have to worry about accidentally unlinking
    // their account on another user's profile page.
    $wp_user = get_user_by('id', get_current_user_id());

    // Get user ID from response
    $oneid_user_id = $attrs["uid"];

    // WordPress user should be logged in
    if ( isset($wp_user) ) {
        // Get WordPress user's oneid_uid
        $wp_oneid_uid = get_user_meta($wp_user->ID, ONEID_UID, true);
        if ($wp_oneid_uid != "") {
            // If oneid_uid is not empty (i.e. user clicked on Unlink)
            // then change the current value to empty string
            update_user_meta($wp_user->ID, ONEID_UID, "", $wp_oneid_uid);
        }
        else {
            // Get all users with this UID
            $user_args = array('meta_key' => ONEID_UID, 'meta_value' => $oneid_user_id);
            if ($users = get_users($user_args) ) {
                // Delete the oneid_uid for all other users with a matching value
                foreach ($users as $user){
                    update_user_meta($user->ID, ONEID_UID, "", $oneid_user_id);
                }
                unset($user);

            }
            // Set this WP user's oneid_uid to their OneID guid
            update_user_meta($wp_user->ID, ONEID_UID, $oneid_user_id);
        }
        // Get the redirect URL
        if ( array_key_exists("redirect_to", $_GET) ) {
            $redirectTo = $_GET['redirect_to'];
        }
        else {
            $redirectTo = admin_url('profile.php', __FILE__);
        }
    }
    else {
        $redirectTo = admin_url('profile.php', __FILE__) . '?error=no_login';
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
