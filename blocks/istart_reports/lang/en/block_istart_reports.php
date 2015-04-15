<?php

/**
 * iStart Reports block strings
 *
 * @package   block_istart_reports
 * @author    Tim Butler
 * @copyright 2015 onwards Harcourts Academy {@link http://www.harcourtsacademy.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Block strings */
$string['blockhasmanagerintro']  = 'Your weekly progress reports are being sent to:';
$string['blockhasmanageraction'] = 'Change';
$string['blocknomanagerintro'] = 'Select your manager to send them a weekly email report highlighting your iStart progress.';
$string['blocknomanageraction'] = 'Select your manager';

/* Managers form */
$string['headermanagerform'] = 'Edit Your Manager';
$string['intromanagerform'] = '<strong>Search</strong> then <strong>Add</strong> your manager(s) below to send them a weekly email report highlighting your iStart progress.';
$string['labelcurrentmanager'] = 'Your current manager(s): ';
$string['existingmanagers'] = 'Your current manager(s)';
$string['existingmanagersmatching'] = 'Matching managers';
$string['labelselectmanager'] = 'Add a new manager: ';
$string['candidatemanagers'] = 'Harcourts Team Members';
$string['candidatemanagersmatching'] = 'Matching Harcourts Team Members';
$string['backtocourse'] = 'Back to iStart';

/* Standard strings */
$string['crontask'] = 'iStart Report scheduled tasks';
$string['noblockid'] = 'Couldn\'t find block id. Ensure this block exists in the block database table.';
$string['nocourse'] = 'Invalid Course with id of {$a}';
$string['istart_reports:addinstance'] = 'Add a istart_reports block';
$string['istart_reports:reporttomanager'] = 'Progress reported to a manager';
$string['pluginname'] = 'iStart Reports';

/* Event logging */
$string['eventmanageradded'] = 'A student\'s manager has been added.';
$string['eventmanagerremoved'] = 'A student\'s manager has been removed.';
$string['eventmanagerreportsent'] = 'An iStart manager report has been sent.';

/**
 * Manager email strings
 */

$string['manageremailsubject'] = 'iStart24 Online Week {$a->istartweeknumber} Progress Report for {$a->firstname} {$a->lastname}';

$string["managerreporttextheader"] = '
{$a->coursename} Progress Report for {$a->firstname} {$a->lastname}
{$a->istartweekname}
------------------------------------------------------------
';

$string['managerreporttextbody'] = '
%{$a->percentcomplete} of {$a->sectionname} tasks complete
';

$string['managerreporttextfooter'] = '
------------------------------------------------------------
Each iStart24 week is structured to include a focus on a particular
aspect of the real estate business. There are four parts to every week.

1. Video to watch
2. Content to read
3. Forum to share
4. Tasks to do

-------------------------------------------------------------------
 Find out more
 (http://www.academyrealestatetraining.com/sales/istart24-online)
-------------------------------------------------------------------

Copyright Harcourts International, All rights reserved.

This email was sent to you because {$a->firstname} {$a->lastname}
nominated you as their manager.

Our mailing address is:
31 Amy Johnson Place
Eagle Farm, QLD 4009
Australia';

$string['managerreporthtmltitle']       = 'iStart24 Online Week {$a->istartweeknumber} progress report for {$a->firstname} {$a->lastname}';
$string['managerreporthtmlheaderintro'] = '{$a->coursename} Progress Report for {$a->firstname} {$a->lastname}';
$string['managerreporthtmlheading']     = '{$a->coursename} Report for {$a->firstname} {$a->lastname}';
$string['managerreporthtmltasksummary'] = '{$a->percentcomplete}% of {$a->sectionname} tasks complete';
$string['managerreporthtmlistartinfo']  = 'Each iStart24 week is structured to include a focus on a particular aspect of the real estate business. '
                                        . 'There are four parts to every week.';
$string['managerreporthtmlwatchlabel']  = 'Video to watch';
$string['managerreporthtmlreadlabel']   = 'Content to read';
$string['managerreporthtmlconnectlabel']= 'Forum to share';
$string['managerreporthtmldolabel']     = 'Tasks to do';
$string['managerreporthtmlactionlabel'] = 'Find out more';
$string['managerreporthtmlactionurl']   = 'http://www.academyrealestatetraining.com/sales/istart24-online';
$string['managerreporthtmlcopyright']   = 'Copyright © Harcourts International, All rights reserved.';
$string['managerreporthtmlreason']      = 'This email was sent to you because {$a->firstname} {$a->lastname} nominated you as their manager.';
$string['managerreporthtmladdress']     = '<strong>Our mailing address is:</strong><br>31 Amy Johnson Place<br>Eagle Farm, QLD 4009<br>Australia';