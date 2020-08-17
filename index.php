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
 * @package    report_coursecompletion
 * @copyright  2017 Catalyst IT Ltd
 * @author     Oliver Redding <oliverredding@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__."/../../config.php");
require_once($CFG->libdir."/adminlib.php");

$course = $DB->get_record('course', array('id' => SITEID));
$userid = $USER->id;

require_login(null, false);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/report/coursecompletion/index.php'));
$context = context_course::instance($course->id);
$systemcontext = context_system::instance();
$personalcontext = context_user::instance($userid);

$PAGE->set_context($personalcontext);

$access = false;
$showallusers = false;
if (has_capability('report/coursecompletion:viewall', $systemcontext)) {
    // User must be site admin or manager - can see records for all users
    $access = true;
    $showallusers = true;
} else if (has_capability('report/coursecompletion:view', $personalcontext)) {
    // User is likely a parent/mentor - can see the student's records only
    $access = true;
} else if (has_capability('report/coursecompletion:view', $context)) {
    // User must be a student - can see the logged in user's records only
    $access = true;
} else if (has_capability('report/coursecompletion:view', $systemcontext)) {
    // User must be authenticated - can see the logged in user's records only
    $access = true;
}

if (!$access) {
    // no access to coursecompletion report!
    print_error('nopermissiontoviewcoursecompletionreport', 'error',  $CFG->wwwroot.'/my');
}

if ($showallusers) {
    admin_externalpage_setup("reportcoursecompletion", "", null, "", array("pagelayout"=>"report"));

} else {
    $PAGE->set_pagelayout('report');
}

// The default column to sort by
$default_sort = $showallusers ? "u.firstname" : "c.fullname";

// Columns of the report
$columns = array(
    "course",
    "timestarted",
    "timecompleted",
    "completionstatus",
);
// Add user columns if user is admin
if ($showallusers) array_unshift($columns, 'name', 'email');

// Sort sql fields for each column
$scolumns = array(
    'course' => array('c.fullname'),
    'timestarted' => array('cc.timestarted'),
    'timecompleted' => array('cc.timecompleted'),
    'completionstatus' => array('completionstatus'),
);
// Add user columns if user is admin
if ($showallusers) {
    $scolumns = array_merge(array(
        'name' => array('u.firstname', 'u.lastname'),
        'email' => array('u.email')
    ), $scolumns);
}

// Variables that hold SQL
$user_where   = "WHERE u.id = :userid";
$user_params  = array('userid' => $USER->id);
$where        = "";
$params       = [];
$cohort_join  = "";

// Build array of all the possible sort columns
$allsorts = array();
foreach($scolumns as $sorts) {
    foreach($sorts as $s) {
        $allsorts[] = $s;
    }
}

$sort = optional_param("sort", $default_sort, PARAM_NOTAGS); // Sorting column.
$dir = optional_param("dir", "ASC", PARAM_ALPHA);            // Sorting direction.
$page = optional_param("page", 0, PARAM_INT);                // Page number.
$perpage = optional_param("perpage", 30, PARAM_INT);         // Results to display per page.
$export = optional_param("export", 0, PARAM_INT);            // Export to csv the results.

/*
 * Sanitize sort and dir to ensure they are valid column sorts.
 * and the dir is either ASC or DESC. This must be done as these
 * values are user supplied and included in the query.
 */

if(!in_array($sort, $allsorts)) {
    $sort = $default_sort;
}
$dir = $dir === 'DESC' ? 'DESC' : 'ASC';

// Ensure the maxiumum records perpage is not ever set too high.
$perpage = min(100, $perpage);

//Intialise mform.
$mform = new \report_coursecompletion\form\report();
$data = $mform->get_data();

//Use the session to hold the form data in case they refresh the page after a post.
if(!$data && isset($USER->session) && isset($USER->session['coursecompletion_formd'])) {
    $data = $USER->session['coursecompletion_formd'];
    $mform->set_data($data);
}

if($data) {//Build the SQL query based on the form data.
    $USER->session['coursecompletion_formd'] = $data;
    if ($showallusers) {
        process_data_field($data, $where, $params, "u.firstname", "firstname", "LIKE");
        process_data_field($data, $where, $params, "u.lastname", "lastname", "LIKE");
        process_data_field($data, $where, $params, "u.email", "email", "LIKE");
        process_data_field($data, $where, $params, "u.suspended", "suspended", "!=");
        process_data_field($data, $where, $params, "u.deleted", "deleted", "!=");
    }

    process_data_field($data, $where, $params, "c.category", "course_categories", "=");
    process_data_field($data, $where, $params, "c.fullname", "course", "LIKE");

    if(isset($data->completed_options)) {
        if($data->completed_options == 1) {
            add_condition_connectors($data, $where, $params);
            $where .= " cc.timecompleted IS NOT NULL";
        } else if($data->completed_options == 2) {
            add_condition_connectors($data, $where, $params);
            $where .= " cc.timecompleted IS NULL";
        }
    }

    process_data_field($data, $where, $params, "cc.timecompleted", "timecompleted_after", ">=");
    process_data_field($data, $where, $params, "cc.timecompleted", "timecompleted_before", "<=");

    process_data_field($data, $where, $params, "cc.timestarted", "timestarted_after", ">=");
    process_data_field($data, $where, $params, "cc.timestarted", "timestarted_before", "<=");

    if($showallusers && isset($data->cohorts) && $data->cohorts != 0) {
        $cohort_join = "LEFT JOIN {cohort_members} AS cm ON u.id = cm.userid AND cm.cohortid = :cohortid";
        add_condition_connectors($data, $where, $params);
        $where .= " cm.id IS NOT NULL ";
        $params["cohortid"] = $data->cohorts;
    }
}

// We need to inlcude user-specific search parameters if the user is not an
// admin, so that only that user's records show.
if (!$showallusers) {
    if ($where == "") $where = $user_where;
    else $where = "$user_where AND ($where)";
}
if (!$showallusers) $params = array_merge($user_params, $params);

if($showallusers && $where != "") {
    $where = "WHERE $where";
}

$order_by = "ORDER BY $sort $dir";

// Get list of user fields for display.
$user_cols = $showallusers ? get_all_user_name_fields(true, 'u') . ", u.email, " : "";

//Generate the final SQL.
$sql = "SELECT cc.id, cc.userid,$user_cols cc.course, c.fullname, cc.timestarted, cc.timecompleted
        FROM {course_completions} AS cc
        JOIN {user} AS u ON cc.userid = u.id
        JOIN {course} AS c ON cc.course = c.id
        $cohort_join $where $order_by";

//The sql for the parameterised count.
$count_sql = "SELECT COUNT(cc.id)
        FROM {course_completions} AS cc
        JOIN {user} AS u ON cc.userid = u.id
        JOIN {course} AS c ON cc.course = c.id
        $cohort_join $where";

//The sql for the total count.
$count_sql_total = "SELECT COUNT(cc.id)
        FROM {course_completions} AS cc
        JOIN {user} AS u ON cc.userid = u.id
        JOIN {course} AS c ON cc.course = c.id
        $user_where";

//If requested, dump to csv instead, use a recordset because the size could grow large.
if($export) {
    $records = $DB->get_recordset_sql($sql, $params);
    // output headers so that the file is downloaded rather than displayed.
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data.csv');

    // create a file pointer connected to the output stream.
    $output = fopen('php://output', 'w');

    //Generate column headers.
    $columnsh = array();
    foreach($columns as $column) {
        $columnsh[] = get_string("table:export_header_$column", 'report_coursecompletion');
    }

    //Output the column headings.
    fputcsv($output, $columnsh);

    //Now dump the data.
    $final = new stdClass();
    foreach($records as $record) {
        if ($showallusers) {
            $final->name = fullname($record);
            $final->email = $record->email;
        }
        $final->course = $record->fullname;
        $final->timestarted = time_format($record->timestarted);
        $final->timecompleted = time_format($record->timecompleted);
        $final->completionstatus = $record->timecompleted ? get_string('yes') : get_string('no');
        fputcsv($output, (array)$final);
    }

    //Close the recordset.
    $records->close();

    //We're done.
    die;
}

//Get records and required count values.
$currentstart = $page * $perpage; //Count of where to start with records
$records = $DB->get_records_sql($sql, $params, $currentstart, $perpage);

$total_record_count = $showallusers ? $DB->count_records("course_completions") : $DB->count_records_sql($count_sql_total, $user_params);
$changes_count = $DB->count_records_sql($count_sql, $params);

//Start display of the page, itself.

echo $OUTPUT->header();
$header_string = $showallusers ? get_string("report_header_admin", "report_coursecompletion") : get_string("report_header", "report_coursecompletion") . fullname($USER, true);
echo $OUTPUT->heading($header_string);

$mform->display();

//Url used for sorting the table.
$base_url = new moodle_url("index.php", array("sort" => $sort, "dir" => $dir, "perpage" => $perpage, "page" => $page));

//Count of records shown as per filter options + href anchor link for jumping back to the table after a search or sort.
$a = new StdClass;
$a->total = $total_record_count;
$a->filter = $changes_count;
echo get_string("count_string", "report_coursecompletion", $a);
echo '<a name="table"></a>';

echo $OUTPUT->paging_bar($changes_count, $page, $perpage, $base_url);

//Calculate table headers (clickable links that do sorting).
$hcolumns = array();
//Foreach column we look at it's applicable sort columns and build a final link header.
foreach($columns as $column) {
    $final = array();
    foreach($scolumns[$column] as $sortcolumn) {
        if($sort != $sortcolumn) {
            $cdir = $dir;
            $cicon = "";
        } else {
            $cdir = $dir == "ASC" ? "DESC" : "ASC";
            $cicondir = ($dir == "ASC") ? "down" : "up";
            $cicon = $OUTPUT->pix_icon('t/'. $cicondir, get_string($cicondir));
        }
        //Get a string for this sort link.
        $column_header = get_string("table:sort_header_$sortcolumn", 'report_coursecompletion');
        //Update parameters for sort and direction for this column in the final url.
        $base_url->param('sort', $sortcolumn);
        $base_url->param('dir', $cdir);
        if ( $sortcolumn === "completionstatus") {
            $final[] = "<a href=javascript:void(0)>$column_header</a>";
        } else {
            $final[] = "<a href=$base_url#table>$column_header</a>$cicon";
        }
    }
    //If one column has multiple sorts, combine them into one entry for that column.
    $hcolumns[$column] = implode('/', $final);
}

$table = new html_table();
$table->head = $hcolumns;
$table->attributes["class"] = "admintable generaltable";
$table->data = [];
foreach($records as $record) {
    $final = new stdClass();
    if ($showallusers) {
        $finalname = fullname($record);
        $final->fullname = html_writer::link(new moodle_url('/user/view.php', array('id' => $record->userid)), $finalname);

        $final->email = $record->email;
    }
    $final->course = html_writer::link(new moodle_url('/course/view.php', array('id' => $record->course)), $record->fullname);
    $final->timestarted = !empty($record->timestarted) ? userdate($record->timestarted) : "-";
    $final->timecompleted = !empty($record->timecompleted) ? userdate($record->timecompleted) : "-";
    $final->completionstatus = $record->timecompleted ? get_string('yes') : get_string('no');
    $table->data[] = $final;
}

echo html_writer::table($table);
$buttonurl = new moodle_url("index.php", array("sort" => $sort, "dir" => $dir, "perpage" => $perpage, "page" => $page, "export"=>1));
$buttonstring = get_string('exportbutton', 'report_coursecompletion');
echo $OUTPUT->single_button($buttonurl, $buttonstring);
echo $OUTPUT->footer();


function process_data_field(&$data, &$where, &$params, $db_field, $field_name, $comparison, $start_group = false, $force_and = false, $end_group = false) {
    if(isset($data->{"$field_name"}) && $data->{"$field_name"}) {
        if(!empty($params)) {
            if($force_and || (isset($data->operator) && $data->operator == 0)) {
                $where .= " AND ";
            } else {
                $where .= " OR ";
            }
        }
        $params[$field_name] = $data->{"$field_name"}.($comparison == "LIKE" ? "%" : "");

        if($start_group) {
            $where .= "(";
        }

        $colon = ":";

        // If the search parameter is a string, make it case insensitive
        if(preg_match("/[a-z]/i", $params[$field_name])) {
            $db_field = "LOWER($db_field)";
            $field_name = "LOWER($colon$field_name)";
            $colon = "";
        }

        $where .= "$db_field $comparison $colon$field_name";

        if($end_group) {
            $where .= ")";
        }
    }
}
