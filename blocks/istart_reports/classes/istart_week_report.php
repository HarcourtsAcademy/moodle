<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace block_istart_reports;

require_once($CFG->dirroot . '/blocks/istart_reports/lib.php');

/**
 * Description of istart_week
 *
 * @author timbutler
 */
class istart_week_report {
    
    public  $reporttype,
            $reporttime,
            $course,
            $totalweeks,
            $istartgroups,
            $istartweeks;
    
    public function __construct($reporttype, $course) {
        $this->reporttype = $reporttype;
        $this->reporttime = getdate();
        $this->course = $course;
        $this->setup_totalweeks($course->id);
        $this->setup_istartgroups($course->id);
        $this->setup_istartweeks($course->id);


    } // _construct

    private function setup_totalweeks($courseid) {
        global $DB;

        // Get total number of istart weeks
        try {

            $sql = '
                    SELECT
                        MAX(CAST(cfo.value AS UNSIGNED)) as totalweeks
                    FROM
                        {course_format_options} AS cfo
                    WHERE
                        cfo.courseid = :courseid
                            AND cfo.name = :format_option_name';
            $params = array(
                            'courseid' => $courseid,
                            'format_option_name'  => 'istartweek');
            $record = $DB->get_record_sql($sql, $params, MUST_EXIST);

        } catch(Exception $e) {
            error_log($e, DEBUG_NORMAL);
            return("iStart manager report not sent because the total iStart weeks cannot be read from the database.");
        }

        $this->totalweeks = $record->totalweeks;
    }

    private function setup_istartweeks($courseid) {
        return true;
    }

    private function setup_istartgroups($courseid) {
        $allgroups = groups_get_all_groups($courseid);

        foreach ($allgroups as $group) {
            $istartgroup = new istart_group($group);
            if ($istartgroup->isvalidgroup) {
                $this->istartgroups[] = $istartgroup;
            }
        }
    }

    /**
    * Sends istart manager reports for a given istart intake group
    * @param stdClass $course The istart course object.
    * @param stdClass $group The group to process.
    * @return TODO true if a report was sent
    */
    function process_manager_reports() {
        if ($this->reporttype !== MANAGERREPORTTYPE) {
            return;
        }

        // Send out all unsent manager reports from the last NUMPASTREPORTDAYS days.
        // Reports older than NUMPASTREPORTDAYS will not be mailed.  This is to avoid the problem where
        // cron has not been running for a long time or a student moves iStart group,
        // and then suddenly people are flooded with mail from the past few weeks or months
        $daysago = 0;

        foreach ($this->istartgroups as $istartgroup) {

            while ($daysago <= NUMPASTREPORTDAYS) {
                $reporttime = strtotime(date("Ymd")) - (DAYSECS * $daysago);
                $istartweek = $istartgroup->get_istart_week($reporttime);
                error_log("2. Started processing group: ".$istartgroup->group->id." (".$istartgroup->group->name."),  Days ago: $daysago, Report time: $reporttime"); // TODO remove after testing
//                process_manager_report_for_group_on_date($course, $istartgroup->group, $reporttime);
                $daysago++;
            }
        }

        return true;
   }

}
