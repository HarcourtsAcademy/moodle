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
 * Add and edit manager email addresses iStart reports are sent to
 *
 * @package   block_istart_reports
 * @copyright Harcourts Academy <academy@harcourts.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class manageremail_form extends moodleform {
    public function definition() {
        global $CFG, $COURSE, $USER;

        $mform = $this->_form;

        $mform->addElement('text', 'manageremail', get_string('labelmanageremail', 'block_istart_reports'), array('size'=>'30'));
        $mform->setType('manageremail', PARAM_TEXT);
        $mform->setDefault('manageremail',get_manager_email_address($USER));

//        $mform->addElement('hidden', 'userid', $USER->id);
//        $mform->setType('userid',PARAM_INT);

        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }


    function validation($data, $files) {
        // TODO validate email address
        return array();
    }
}