<?php
/**
 * Snipcart enrolment plugin
 *
 * @package   enrol_snipcart
 * @author    Tim Butler
 * @copyright (c) 2015 Harcourts International Limited {@link http://www.harcourtsacademy.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('SOCIAL_USERNAME_PREFIX', 'social_user_');

require_once($CFG->libdir . '/filelib.php'); // curl
require_once("classes/event/snipcartorder_completed.php");
require_once("classes/snipcartaccounts.php");

class enrol_snipcart_plugin extends enrol_plugin {

    public function roles_protected() {
        // Users may tweak the roles later.
        return false;
    }
    
    private $currencies = array(
        'AU' => 'AUD',
        'NZ' => 'NZD',
        'US' => 'USD',
        'ZA' => 'ZAR',
    );
    
    /**
     * Sets up navigation entries.
     *
     * @param object $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'snipcart') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/snipcart:config', $context)) {
            $managelink = new moodle_url('/enrol/snipcart/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    public function allow_unenrol(stdClass $instance) {
        // Users with unenrol cap may unenrol other users manually.
        return true;
    }

    public function allow_manage(stdClass $instance) {
        // Users with manage cap may tweak period and status.
        return true;
    }
    
    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/snipcart:config', $context);
    }
    
    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/snipcart:config', $context);
    }
    
    /**
     * Is it possible for the user to buy access using this instance?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_user_access_instance($instance) {
        global $DB, $USER;
        
        $enrol = $DB->get_record('enrol', array('id'=>$instance->id));
        $user = $DB->get_record('user', array('id'=>$USER->id)); // ensure current user info
        
        // Social (public) users are prevented from accessing
        // the course if so configured and this user is a social user.
        if ($enrol->customint1 == ENROL_INSTANCE_DISABLED && 
                stripos($user->username, SOCIAL_USERNAME_PREFIX) === 0) {
            return false;
        }
        
        // Only let users pay for courses in their own currency
        if (stripos($enrol->currency, $user->country) !== 0) {
            return false;
        }
        
        return true;
    }
    
    public function cron() {
        $trace = new text_progress_trace();
        $this->process_expirations($trace);
    }
    
    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'snipcart') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/snipcart:config', $context)) {
            $editlink = new moodle_url("/enrol/snipcart/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core',
                    array('class' => 'iconsmall')));
        }

        return $icons;
    }
    
    /**
     * Returns course 'Add to cart' button.
     *
     * @param object $course
     * @return string of button html
     */
    function get_add_to_cart_button($course) {
        global $CFG, $DB, $USER;
        
        // Notify the admin if a user's country is not set (ignore the guest user)
        if (!($USER->country) and !($USER->id == 1)) {
            $this->message_error_to_admin('A Moodle user cannot purchase a course because their country is not set', $USER);
        }
        
        $buttons = '';
        
        $instances = $DB->get_records('enrol', array('enrol'=>'snipcart', 'courseid'=>$course->id, 'status'=>ENROL_INSTANCE_ENABLED), 'sortorder,id');
        
        foreach ($instances as $instance) {
            if ($instance->status != ENROL_INSTANCE_ENABLED or $instance->courseid != $course->id) {
                debugging('Invalid instances parameter submitted in enrol_get_info_icons()');
                continue;
            }
            
            // TODO: check if use is enrolled via ANY enrolment method not just this one.
            if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
                continue;
            }
            
            if (!$this->can_user_access_instance($instance)) {
                continue;
            }

            if ( (float) $instance->cost <= 0 ) {
                $cost = (float) $instance->get_config('cost');
            } else {
                $cost = (float) $instance->cost;
            }

            if (abs($cost) < 0.01) { // no cost, other enrolment methods (instances) should be used
                continue;
            } else {

                $params = array('uid' => $USER->id,
                                'eid' => $instance->id);
                $itemurl = str_replace("http://", "https://", new moodle_url("/enrol/snipcart/validate.php", $params));
                
                $localisedcost = $this->get_localised_currency($instance->currency, format_float($cost, 2, true));
                $cost = format_float($cost, 2, false);
                
                $context = context_course::instance($course->id);

                //Sanitise some fields before building the Snipcart code
                $coursefullname  = format_string($course->fullname, true, array('context'=>$context));
                $courseshortname = format_string($course->shortname, true, array('context' => $context));
                
                // get the first course image
                require_once($CFG->libdir. '/coursecatlib.php');
                $courseoverviewfiles = $course->get_course_overviewfiles();
                $firstfile = array_shift($courseoverviewfiles);

                $isimage = (!empty($firstfile) and $firstfile->is_valid_image());

                if ($isimage) {
                    $courseimageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $firstfile->get_contextid(). '/'. $firstfile->get_component(). '/'.
                            $firstfile->get_filearea(). $firstfile->get_filepath(). $firstfile->get_filename(), true);
                } else {
                    $courseimageurl = new \moodle_url('/enrol/snipcart/pix/empty-course-icon.png');
                }

                // Generate id for 'Add to cart' button based on course id
                $addtocartid = 'addtocart' . $course->id;

                
                $buttons.= "
                    <a href='#' id='$addtocartid' class='snipcart-actions invisible btn btn-primary btn-small pull-right'>".get_string('addtocart', 'enrol_snipcart', array('currency'=>$instance->currency, 'cost'=>$localisedcost)) . "</a>
                            
                    <script type='text/javascript'>
                        $(window).load(function() {
                            Snipcart.execute('bind', 'order.completed', function (order) {
                                var path = location.pathname.substring(0, location.pathname.lastIndexOf('/'));
                                var url = path + '/snipcart/completed.php?order=' + order.token + '&eid={$instance->id}';
                                window.location.href = url;
                            });

                            $('#$addtocartid')
                                .click(function(e){
                                    // Cancel the default action
                                    e.preventDefault();

                                    Snipcart.execute('item.add', {
                                        id: '{$USER->id}-{$instance->id}',
                                        name: '$coursefullname',
                                        price: '{$instance->cost}',
                                        maxQuantity: '1',
                                        quantity: '1',
                                        shippable: 'false',
                                        url: '$itemurl',
                                        description: '{$course->summary}',
                                        image: '$courseimageurl'
                                    });

                                    var oldLabel = $(this).html();
                                    $(this).addClass('btn-disabled');
                                    $(this).addClass('disabled');
                                    $(this).removeClass('btn-primary');
                                    $(this).removeAttr('data-toggle');
                                    var width = $(this).css('width');
                                    var height = $(this).css('height');
                                    $(this).html('". get_string('addedtocart', 'enrol_snipcart', array('currency'=>$instance->currency, 'cost'=>$localisedcost)) ."');
                                    var newWidth = $(this).css('width');
                                    $(this).html('');
                                    $(this).css({'width': width, 'height': height});
                                    $(this).animate({'width': newWidth}, 300, function() {
                                        $(this).html('". get_string('addedtocart', 'enrol_snipcart', array('currency'=>$instance->currency, 'cost'=>$localisedcost)) ."');
                                      });

                                 });
                        });

                    </script>";
            }
            
        }
        return $buttons;
    }
    
    public function get_currencies() {
        $codes = array(
            'AUD', 'NZD', 'USD', 'ZAR');
        $currencies = array();
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }

        return $currencies;
    }
    
    /**
     * Returns the currency for a given country
     * @param string $country as the two-digit code
     * @return string
     */
    public function get_currency_for_country($country) {
//        foreach (CURRENCIES as $country => $code) {
//            $currencies[$c] = new lang_string($c, 'core_currencies');
//        }

        return (empty($country)) ? null : $this->currencies[$country];
    }
    
    public function get_localised_currency($currency, $cost) {
        
        if (empty($currency) or empty($cost)) {
            return;
        }
        
        $manager = \enrol_snipcart\get_snipcartaccounts_manager();
        
        $snipcartaccounts = $manager->get_snipcartaccounts();
        
        $symbols = array();
        foreach ($snipcartaccounts as $account) {
            $symbols[$account->currencycode] = $account->currencyformat;
        }
        
        return str_replace('%c', $cost, $symbols[$currency]);
    }
    
    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        if (empty($instance->name)) {
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol);
        } else {
            $context = context_course::instance($instance->courseid);
            return format_string("{$instance->name} {$instance->currency} " . 
                    $this->get_localised_currency($instance->currency, format_float($instance->cost, 2, false)),
                    true, array('context'=>$context));
        }
    }
    
    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/snipcart:config', $context)) {
            return NULL;
        }

        return new moodle_url('/enrol/snipcart/edit.php', array('courseid'=>$courseid));
    }
    
    /**
     * Gets an array of the user enrolment actions
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol($instance) && has_capability("enrol/snipcart:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
        }
        if ($this->allow_manage($instance) && has_capability("enrol/snipcart:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, array('class'=>'editenrollink', 'rel'=>$ue->id));
        }
        return $actions;
    }
    
    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $enrol) {
        global $CFG, $USER, $OUTPUT, $DB;
        
        // Notify the admin if a user's country is not set (ignore the guest user)
        if (!($USER->country) and !($USER->id == 1)) {
            $this->message_error_to_admin('A Moodle user cannot purchase a course because their country is not set', $USER);
        }

        ob_start();
        
        if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$enrol->id))) {
            return ob_get_clean();
        }
        
        if ($enrol->enrolstartdate != 0 && $enrol->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($enrol->enrolenddate != 0 && $enrol->enrolenddate < time()) {
            return ob_get_clean();
        }
        
        if (!$this->can_user_access_instance($enrol)) {
            return ob_get_clean();
        }
        
        if ( (float) $enrol->cost <= 0 ) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $enrol->cost;
        }

        if (abs($cost) < 0.01) { // no cost, other enrolment methods (instances) should be used
            echo '<p>'.get_string('nocost', 'enrol_snipcart').'</p>';
        } else {

            $course = $DB->get_record('course', array('id'=>$enrol->courseid));
            $user = $USER;
            $plugin = enrol_get_plugin('snipcart');
            $userid = $USER->id;
            $enrolid = $enrol->id;
        
            include($CFG->dirroot.'/enrol/snipcart/enrol.html');
        }
        
        return $OUTPUT->box(ob_get_clean());
    }
    
    function message_error_to_admin($subject, $data) {
        $admin = get_admin();
        $site = get_site();

        $message = "$site->fullname:  Snipcart failure.\n\n$subject\n\n";

        $eventdata = new stdClass();
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_snipcart';
        $eventdata->name              = 'snipcart_enrolment';
        $eventdata->userfrom          = $admin;
        $eventdata->userto            = $admin;
        $eventdata->subject           = "Snipcart Error: ".$subject;
        $eventdata->fullmessage       = $message . print_r($data, true);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
    }
    
    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = array(
                'courseid'   => $data->courseid,
                'enrol'      => $this->get_name(),
                'roleid'     => $data->roleid,
                'cost'       => $data->cost,
                'currency'   => $data->currency,
            );
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }
    
    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }
    
    /**
     * Student's can self-enrol buy paying for the course
     *
     * @param stdClass $instance course enrol instance
     *
     * @return bool - true means show "Enrol me in this course" link in course UI
     */
    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }
    
    /**
     * Get a order token from an order array
     *
     * @param array $order as array
     *
     * @return string containing the order token
     */
    public function snipcart_get_ordertoken($order) {
        return $order['content']['token'];
    }
    
    /**
     * Get a order currency from an order array
     *
     * @param array $order as array
     *
     * @return string containing the order token
     */
    public function snipcart_get_ordercurrency($order) {
        global $DB;
        
        $itemid = explode('-', $order['content']['items'][0]['id']);
        
        if (! $enrol = $DB->get_record('enrol', array('id'=>$itemid[1]))) {
            header('HTTP/1.1 400 BAD REQUEST');
            throw new moodle_exception('snipcartinvalidorderror', 'enrol_snipcart', null, array('token'=>$token, 'currency'=>$currency));
        }
        
        return $enrol->currency;
    }
    
    /**
     * Updates the Moodle user account with new information from Snipcart order
     *
     * @param stdClass $snipcartorder
     *
     * @return bool true if user updated, false otherwise
     */
    public function snipcart_update_user($snipcartorder) {
        global $DB;
        
        $user = $snipcartorder->user;
        
        if (empty($user)) {
            return false;
        }
        
        $userupdated = false;
        
        if (empty($user->city) and $snipcartorder->get_user_field('billingAddressCity') != 'null') {
            $user->city = $snipcartorder->get_user_field('billingAddressCity');
            $userupdated = true;
        }
        
        if (empty($user->address) and $snipcartorder->get_user_field('billingAddressAddress1') != 'null') {
            $user->address = $snipcartorder->get_user_field('billingAddressAddress1') . 
                    ', ' . $snipcartorder->get_user_field('billingAddressAddress2');
            $userupdated = true;
        }
        
        $postcodefieldid = $DB->get_field('user_info_field', 'id', array( 'shortname' => 'postcode'));
        $postcodefield = $DB->get_record('user_info_data', array('userid' => $user->id, 'fieldid' => $postcodefieldid));
        
        if (empty($postcodefield->data) and $snipcartorder->get_user_field('billingAddressPostalCode') != 'null' ) {
            $customfield = new stdClass;
            $customfield->userid = $user->id;
            $customfield->fieldid = $postcodefieldid;
            $customfield->data = $snipcartorder->get_user_field('billingAddressPostalCode');

            if ( !$DB->record_exists( 'user_info_data', array( 'userid' => $user->id, 'fieldid' => $postcodefieldid ) ) ) {
                $DB->insert_record('user_info_data', $customfield);
            } else {
                $record = $DB->get_record( 'user_info_data', array( 'userid' => $user->id, 'fieldid' => $postcodefieldid ) );
                $customfield->id = $record->id;
                $DB->update_record('user_info_data', $customfield);
            }
        }
        
        if (empty($user->phone1) and $snipcartorder->get_user_field('billingAddressPhone') != 'null') {
            $user->phone1 = $snipcartorder->get_user_field('billingAddressPhone');
            $userupdated = true;
        }
        
        if ($userupdated) {
            $DB->update_record('user', $user);
            return true;
        }
        
        return false;
    }
    
    /**
     * Enrols student in the course they purchased
     *
     * @param stdClass $user to be enrolled
     * @param stdClass $enrol instance
     * @param string $ordertoken of the order for the event log
     *
     * @return stdClass Moodle course
     */
    public function snipcart_enrol_user($user, $enrol, $ordertoken) {
        global $DB;
        
        $course = $DB->get_record("course", array('id'=>$enrol->courseid));
        
        $context = context_course::instance($course->id, IGNORE_MISSING);

        if ($enrol->enrolperiod) {
            $timestart = time();
            $timeend   = $timestart + $enrol->enrolperiod;
        } else {
            $timestart = 0;
            $timeend   = 0;
        }
        
        // Log the purchase of the course enrolment
        $event = \enrol_snipcart\event\snipcartorder_completed::create(array(
            'context' => $context,
            'userid' => $user->id,
            'courseid' => $course->id,
            'objectid' => $enrol->id,
            'other' => $ordertoken,
        ));
        $event->trigger();

        
        // Todo: Email the student a link to get started
                
        // Enrol the student in each of the course they have purchased
        return $this->enrol_user($enrol, $user->id, $enrol->roleid, $timestart, $timeend);
    }
    
    /**
     * Unenrols student in the course they purchased when the order is cancelled
     *
     * @param stdClass $user to be enrolled
     * @param stdClass $enrol instance
     * @param string $ordertoken of the order for the event log
     *
     * @return stdClass Moodle course
     */
    public function snipcart_unenrol_user($user, $enrol, $ordertoken) {
        global $DB;
        
        $course = $DB->get_record("course", array('id'=>$enrol->courseid));
        
        $context = context_course::instance($course->id, IGNORE_MISSING);
        
        // Log the order cancellation
        $event = \enrol_snipcart\event\snipcartorder_cancelled::create(array(
            'context' => $context,
            'userid' => $user->id,
            'courseid' => $course->id,
            'objectid' => $enrol->id,
            'other' => $ordertoken,
        ));
        $event->trigger();

        // Unenrol the student in each of the courses they purchased
        return $this->unenrol_user($enrol, $user->id);
    }

}

