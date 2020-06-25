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
 * Coursecompletion report
 *
 * @package    report
 * @subpackage coursecompletion
 * @copyright  2017 Catalyst IT Ltd
 * @author     Francis Devine <francis@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    $string['pluginname'] = 'Course completion';
    $string['coursecompletion'] = 'Course completion';
    $string['report_header_admin'] = 'Course completion records for all users';
    $string['report_header'] = 'Course completion record for ';
    $string['count_string'] = '{$a->filter} shown of total: {$a->total}';
    $string['exportbutton'] = 'Export csv';

    $string['table:export_header_name'] = 'Fullname';
    $string['table:export_header_email'] = 'Email';
    $string['table:export_header_course'] = 'Course';
    $string['table:export_header_timestarted'] = 'Time started';
    $string['table:export_header_timecompleted'] = 'Time completed';
    $string['table:export_header_completionstatus']  = 'Complete';

    $string['table:sort_header_u.firstname']  = 'Firstname';
    $string['table:sort_header_u.lastname']  = 'Lastname';
    $string['table:sort_header_u.email']  = 'Email';
    $string['table:sort_header_c.fullname']  = 'Course';
    $string['table:sort_header_cc.timecompleted']  = 'Timecompleted';
    $string['table:sort_header_cc.timestarted']  = 'Timestarted';
    $string['table:sort_header_completionstatus']  = 'Complete';

    $string['form:active'] = 'Show active';
    $string['form:suspended'] = 'Hide suspended';
    $string['form:deleted'] = 'Hide deleted';
    $string['form:cohorts'] = 'Cohort:';
    $string['form:any_cohort'] = 'Any';
    $string['form:section_userdetails'] = 'User details';
    $string['form:firstname'] = 'First name:';
    $string['form:lastname'] = 'Last name:';
    $string['form:email'] = 'Email:';
    $string['form:completed_options'] = 'Course completion';
    $string['form:completed_options_any'] = 'Any';
    $string['form:completed_options_completed'] = 'Completed';
    $string['form:completed_options_not_completed'] = 'Not completed';
    $string['form:search'] = 'Search';
    $string['form:section_coursedetails'] = 'Course details';
    $string['form:course_categories'] = 'Category:';
    $string['form:any_category'] = 'Any';
    $string['form:course'] = 'Course:';
    $string['form:section_timecompleted'] = 'Time completed';
    $string['form:filter_by_timecompleted'] = 'Filter by time completed:';
    $string['form:timecompleted_after'] = 'Completed after:';
    $string['form:timecompleted_before'] = 'Completed before:';
    $string['form:section_timestarted'] = 'Time started';
    $string['form:filter_by_timestarted'] = 'Filter by time started:';
    $string['form:timestarted_after'] = 'Started after:';
    $string['form:timestarted_before'] = 'Started before:';
    $string['form:operator_and'] = 'All conditions';
    $string['form:operator_or'] = 'Any condition';
    $string['form:operator'] = 'Show records that match:';

    // Privacy API.
    $string['privacy:no_userid_data'] = 'The course completion plugin does not share data to external services, store user preferences, or identify users in its database.';
