<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin internal classes, functions and constants are defined here.
 *
 * @package     local_wordpresssync
 * @copyright   2020 Harcourts International Pty Ltd <academy@harcourts.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('WP_USER_ENDPOINT','wp-json/wp/v2/users/');

/**
 * Handle the \core\event\user_created event.
 *
 * @param object $event The event object.
 */
function user_created(\core\event\user_created $event) {
    $newuser = $event->get_record_snapshot('user', $event->objectid);
    sync_user_to_wordpress($newuser);
    return;
}

function sync_user_to_wordpress($user) {
    global $DB, $CFG;

    // Don't sync incomplete users
    if (!$user->email)
        return true;

    /* Create user in WordPress */
    $user->name = $user->firstname. " " . $user->lastname;
    create_wp_user($user);
}

/**
 * Calls WordPress API to create user
 *
 * @param stdClass $user
 * @return true if token is valid
 * @return false if token is invalid
 */
function create_wp_user($user) {
    global $CFG, $ADMIN;

    // Don't create incomplete users
    if (!$user->email)
        return true;

    if( !function_exists("curl_init") &&
        !function_exists("curl_setopt") &&
        !function_exists("curl_exec") &&
        !function_exists("curl_close") ) die ("cURL not installed.");


    $wpurl = get_config('local_wordpresssync', 'wpurl')
        . WP_USER_ENDPOINT;
    $wpusername = get_config('local_wordpresssync', 'wpusername');
    $wppassword = get_config('local_wordpresssync', 'wppassword');

    if (!isset($wpurl) || !isset($wpusername) || !isset($wppassword)) {
        error_log('WordPress settings not yet configured.');
        return false;
    }

    if (!preg_match('|^https://|i', $wpurl)) {
        error_log('WordPress URL must use HTTPS.');
        return false;
    }

    $post['username']   = $user->username;
    $post['email']      = $user->email;
    $post['password']   = $user->password;
    $post['first_name']  = $user->firstname;
    $post['last_name']   = $user->lastname;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $wpurl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    // REMOVE before production
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    ///////////////////////////
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data"));
    curl_setopt($ch, CURLOPT_USERPWD, $wpusername . ":" . $wppassword);



    // Execute the request
    $response = curl_exec($ch);

    // Get the HTTP status from the response header.
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if( $httpcode>=200 && $httpcode<300 ) {
        $newwpuser = json_decode($response);
        if(!isset($newwpuser))
            return false;

        // Update the Moodle user data with their WordPress User ID
        echo "New WP UserID = " . $newwpuser->id;
        $user->profile["wpuserid"] = $newwpuser->id;

        echo "<p>Updating user profile.</p>";
        update_user_profile($user->id,$newwpuser->id);
    } else {
        error_log("local_wordpresssync: Couldn't create WordPress user " . $user->username);
        error_log("local_wordpresssync: WordPress error: " . $response);
    }

    // close cURL resource, and free up system resources
    curl_close($ch);

    return true;
}

function update_user_profile($userid, $wpuserid) {
    global $CFG;

    require_once($CFG->dirroot.'/user/profile/lib.php');

    profile_save_data((object)['id' => $userid, 'profile_field_wpuserid' => $wpuserid]);
}
