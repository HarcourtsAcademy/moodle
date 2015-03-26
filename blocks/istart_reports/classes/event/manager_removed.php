<?php
/**
 * The manager_removed event class.
 *
 * @property-read array $other {
 *      Event logged when a student's manager is unassigned.
 * }
 *
 * @since     Moodle 2014051207.00
 * @package   block_istart_reports
 * @author    Tim Butler
 * @copyright 2015 onwards Harcourts Academy {@link http://www.harcourtsacademy.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

namespace block_istart_reports\event;

defined('MOODLE_INTERNAL') || die();

class manager_removed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'd'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'role_assignments';
    }

    public static function get_name() {
        return get_string('eventmanagerremoved', 'block_istart_reports');
    }

    public function get_description() {
        return "The user with id {$this->userid} removed their manager {$this->relateduserid}.";
    }

    public function get_url() {
        return new \moodle_url("/admin/roles/assign.php",
                array('contextid' => $this->contextid,
                    'userid' => $this->userid,
                    'courseid' => '1'));
    }
}
