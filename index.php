<?php
    require_once(__DIR__."/../../config.php");
    require_once($CFG->libdir."/adminlib.php");
    require_once($CFG->dirroot.'/report/coursecompletion/forms.php');

    $system_context = context_system::instance();
    require_capability('report/coursecompletion:viewreport', $system_context, null, true, "You do not have permission to access this page.");

    admin_externalpage_setup("reportcoursecompletion", "", null, "", array("pagelayout"=>"report"));

    // The default column to sort by.
    $default_sort = 'u.firstname';

    //Columns of the report.
    $columns = array(
        "name",
        "email",
        "course",
        "timestarted",
        "timecompleted",
    );

    //Sort sql fields for each column.
    $scolumns = array(
        'name' => array('u.firstname', 'u.lastname'),
        'email' => array('u.email'),
        'course' => array('c.fullname'),
        'timestarted' => array('cc.timestarted'),
        'timecompleted' => array('cc.timecompleted'),
    );
    //build array of all the possible sort columns
    $allsorts = array()
    foreach($scolumns as $sorts) {
        foreach($sorts as $s) {
            $allsorts[] = $s;
        }
    }

    $sort = optional_param("sort", $default_sort, PARAM_NOTAGS); // Sorting column.
    $dir = optional_param("dir", "ASC", PARAM_ALPHA);           // Sorting direction.
    $page = optional_param("page", 0, PARAM_INT);               // Page number.
    $perpage = optional_param("perpage", 30, PARAM_INT);        // Results to display per page.
    $export = optional_param("export", 0, PARAM_INT);        // Export to csv the results.

    /*
     * Sanitize sort and dir to ensure they are valid column sorts.
     * and the dir is either ASC or DESC. This must be done as these
     * values are user supplied and included in the query.
     */
    if(!in_array($sorts, $allsorts)) {
        $sort = $default_sort;
    }
    if($dir != 'ASC' || $dir != 'DESC') {
        $dir = 'ASC';
    }

    //Variables that hold SQL.
    $where = "";
    $cohort_join = "";
    $params = [];

    //Intialise mform.
    $mform = new ReportForm();
    $data = null;
    $data = $mform->get_data();
    //Use the session to hold the form data in case they refresh the page after a post.
    if(!$data && isset($USER->session) && isset($USER->session['coursecompletion_formd'])) {
        $data = $USER->session['coursecompletion_formd'];
        $mform->set_data($data);
    }
    if($data) {//Build the SQL query based on the form data.
        $USER->session['coursecompletion_formd'] = $data;
        process_data_field($data, $where, $params, "u.firstname", "firstname", "LIKE");
        process_data_field($data, $where, $params, "u.lastname", "lastname", "LIKE");
        process_data_field($data, $where, $params, "u.email", "email", "LIKE");
        process_data_field($data, $where, $params, "u.suspended", "suspended", "!=");
        process_data_field($data, $where, $params, "u.deleted", "deleted", "!=");

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

        if(isset($data->cohorts) && $data->cohorts != 0) {
            $cohort_join = "LEFT JOIN {cohort_members} AS cm ON u.id = cm.userid AND cm.cohortid = :cohortid";
            add_condition_connectors($data, $where, $params);
            $where .= " cm.id IS NOT NULL ";
            $params["cohortid"] = $data->cohorts;
        }

        if($where != "") {
            $where = "WHERE $where";
        }
    }

    $order_by = "ORDER BY $sort $dir";

    //Get list of name fields for display.
    $namesql = get_all_user_name_fields($returnsql = true, 'u');

    //Generate the final SQL.
    $sql = "SELECT cc.id, cc.userid, $namesql, u.email, cc.course, c.fullname, cc.timestarted, cc.timecompleted, cc.timestarted
            FROM {course_completions} AS cc
            JOIN {user} AS u ON cc.userid = u.id
            JOIN {course} AS c ON cc.course = c.id
            $cohort_join $where $order_by";

    //The sql for the count.
    $count_sql = "SELECT COUNT(cc.id)
            FROM {course_completions} AS cc
            JOIN {user} AS u ON cc.userid = u.id
            JOIN {course} AS c ON cc.course = c.id
            $cohort_join $where";

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
            $final->name = fullname($record);
            $final->email = $record->email;
            $final->course = $record->fullname;
            $final->timestarted = time_format($record->timestarted);
            $final->timecompleted = time_format($record->timecompleted);
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

    $total_record_count = $DB->count_records("course_completions");
    $changes_count = $DB->count_records_sql($count_sql, $params);

    //Start display of the page, itself.

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("report_header", "report_coursecompletion"));

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
                $column_dir = $dir;
                $column_icon = "";
            } else {
                $column_dir = $dir == "ASC" ? "DESC" : "ASC";
                $column_icon_dir = ($dir == "ASC") ? "down" : "up";
                $column_icon = " <img src=\"" . $OUTPUT->pix_url("t/" . $column_icon_dir) . "\" alt=\"\" />";
            }
            //Get a string for this sort link.
            $column_header = get_string("table:sort_header_$sortcolumn", 'report_coursecompletion');
            //Update parameters for sort and direction for this column in the final url.
            $base_url->param('sort', $sortcolumn);
            $base_url->param('dir', $column_dir);
            $final[] = "<a href=$base_url#table>$column_header</a>$column_icon";
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
        $finalname = fullname($record);
        $final->fullname = add_link('/user/view.php', array('id' =>$record->userid), $finalname);
        $final->email = $record->email;
        $final->course = add_link('/course/view.php', array('id' =>$record->course), $record->fullname);
        $final->timestarted = time_format($record->timestarted);
        $final->timecompleted = time_format($record->timecompleted);
        $table->data[] = $final;
    }

    echo html_writer::table($table);
    $buttonurl = new moodle_url("index.php", array("sort" => $sort, "dir" => $dir, "perpage" => $perpage, "page" => $page, "export"=>1));
    $buttonstring = get_string('exportbutton', 'report_coursecompletion');
    echo $OUTPUT->single_button($buttonurl, $buttonstring);
    echo $OUTPUT->footer();

    function add_condition_connectors(&$data, &$where, &$params, $force_and = false) {
        if(!empty($params)) {
            if($force_and || (isset($data->operator) && $data->operator == 0)) {
                $where .= "AND";
            } else {
                $where .= "OR";
            }
        }
    }

    function add_link($url, $params, $string) {
        return html_writer::link(new moodle_url($url, $params), $string);
    }

    function process_data_field(&$data, &$where, &$params, $db_field, $field_name, $comparison, $start_group = false, $force_and = false, $end_group = false) {
        if(isset($data->{"$field_name"}) && $data->{"$field_name"}) {
            add_condition_connectors($data, $where, $params, $force_and);

            if($start_group) {
                $where .= "(";
            }

            $where .= " $db_field $comparison :$field_name ";

            if($end_group) {
                $where .= ")";
            }

            $params[$field_name] = $data->{"$field_name"}.($comparison == "LIKE" ? "%" : "");
        }
    }

    function time_format($time) {
        if(isset($time) && $time != 0) {
            return userdate($time);
        } else {
            return "-";
        }
    }
?>
