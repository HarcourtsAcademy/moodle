<?php

/**
 * Listens for payment notification from Snipcart
 *
 * This script waits for payment notification from Snipcart,
 * then double checks that data by sending it back to Snipcart.
 * If Snipcart verifies this then it sets up the enrolment for that
 * user.
 *
 * @package   enrol_snipcart
 * @author    Tim Butler
 * @copyright (c) 2015 Harcourts International Limited {@link http://www.harcourtsacademy.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

require("../../config.php");
require_once("lib.php");

$json = file_get_contents('php://input');

/// Keep out casual intruders
if (empty($json) or !empty($_GET)) {
    header('HTTP/1.1 400 Bad Request');
    return;
}

$body = json_decode($json, true);

if (is_null($body) or !isset($body['eventName'])) {
    // When something goes wrong, return an invalid status code
    // such as 400 BadRequest.
    header('HTTP/1.1 400 Bad Request');
    return;
}

switch ($body['eventName']) {
    case 'order.completed':
        $plugin = enrol_get_plugin('snipcart');
        
        $validatedorder = $plugin->snipcart_get_order($body['content']['token']);
        
// todo: remove         $validatedorder = $body['content']; // todo: remove after local testing
        
        if (empty($validatedorder)) {
            error_log('Invalid Snipcart order: ' . print_r($body, true));
            header('HTTP/1.1 400 BAD REQUEST');
            die;
        }
        
        foreach ($validatedorder['items'] as $orderitem) {
            $plugin->snipcart_enrol_user($orderitem);
        }

        // Update the user's address, city and postcode if not set in Moodle
        $plugin->snipcart_update_user($validatedorder);
        
        break;
        
    case 'order.status.changed':
        $plugin = enrol_get_plugin('snipcart');
        
        $validatedorder = $plugin->snipcart_get_order($body['content']['token']);
        
// todo: remove        $validatedorder = $body['content']; // todo: remove after local testing
        
        if (empty($validatedorder)) {
            error_log('Invalid Snipcart order: ' . print_r($body, true));
            // Return a valid status code such as 200 OK.
            header('HTTP/1.1 400 BAD REQUEST');
            die;
        }
        
        // Unenrol the student if the order was cancelled
        if ($validatedorder['status'] == 'Cancelled') {
            foreach ($validatedorder['items'] as $orderitem) {
                $plugin->snipcart_unenrol_user($orderitem);
            }
        }
        
        break;
}

// Return a valid status code such as 200 OK.
header('HTTP/1.1 200 OK');