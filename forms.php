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

defined('MOODLE_INTERNAL') || die();

require(__DIR__ . "/../../config.php");
require_login();
require_once($CFG->libdir . "/formslib.php");

class ReportForm extends moodleform
{
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        if (REPORT_COURSECOMPLETION_IS_ADMIN) {
            $mform->addElement("header", "section_userdetails", get_string("form:section_userdetails", "report_coursecompletion"));
            $mform->addElement("text", "firstname", get_string("form:firstname", "report_coursecompletion"));
            $mform->setType("firstname", PARAM_ALPHA);
            $mform->addElement("text", "lastname", get_string("form:lastname", "report_coursecompletion"));
            $mform->setType("lastname", PARAM_ALPHA);
            $mform->addElement("text", "email", get_string("form:email", "report_coursecompletion"));
            $mform->setType("email", PARAM_NOTAGS);
            $cohorts = $this->get_cohorts();
            $mform->addElement("select", "cohorts", get_string("form:cohorts", "report_coursecompletion"), $cohorts);
            $mform->setDefault("cohorts", 0);

            $mform->addElement("advcheckbox", "active", get_string("form:active", "report_coursecompletion"));
            $mform->addElement("advcheckbox", "suspended", get_string("form:suspended", "report_coursecompletion"));
            $mform->addElement("advcheckbox", "deleted", get_string("form:deleted", "report_coursecompletion"));
        }

        $mform->addElement("header", "section_coursedetails", get_string("form:section_coursedetails", "report_coursecompletion"));
        $categories = $this->get_course_categories();
        $mform->addElement("select", "course_categories",
            get_string("form:course_categories", "report_coursecompletion"),
            $categories);
        $mform->setDefault("course_categories", 0);
        $mform->addElement("text", "course", get_string("form:course", "report_coursecompletion"));
        $completeoptions = [
            get_string("form:completed_options_any", "report_coursecompletion"),
            get_string("form:completed_options_completed", "report_coursecompletion"),
            get_string("form:completed_options_not_completed", "report_coursecompletion")
        ];

        $mform->addElement("select", "completed_options",
            get_string("form:completed_options", "report_coursecompletion"),
            $completeoptions);
        $mform->setType("course", PARAM_TEXT);
        $options = ['optional' => true];
        $mform->addElement("header", "section_timecompleted", get_string("form:section_timecompleted", "report_coursecompletion"));
        $mform->setExpanded("section_timecompleted", false);
        $mform->addElement('date_selector', 'timecompleted_after',
            get_string("form:timecompleted_after", "report_coursecompletion"),
            $options);
        $mform->addElement('date_selector', 'timecompleted_before',
            get_string("form:timecompleted_before",
                "report_coursecompletion"),
                $options);

        $mform->addElement("header", "section_timestarted",
            get_string("form:section_timestarted",
                "report_coursecompletion"));

        $mform->addElement('date_selector', 'timestarted_after',
            get_string("form:timestarted_after", "report_coursecompletion"),
            $options);
        $mform->addElement('date_selector', 'timestarted_before',
            get_string("form:timestarted_before", "report_coursecompletion"),
            $options);
        $mform->closeHeaderBefore("search_operators");
        $mform->setExpanded("section_timecompleted", false);

        $radioarray = [];
        $radioarray[] = $mform->createElement("radio", "operator", "",
            get_string("form:operator_and", "report_coursecompletion"), 0);
        $radioarray[] = $mform->createElement("static", "space", "", "<br>");
        $radioarray[] = $mform->createElement("radio", "operator", "",
            get_string("form:operator_or", "report_coursecompletion"), 1);
        $mform->addGroup($radioarray, "search_operators",
            get_string("form:operator", "report_coursecompletion"),
            array(" "), false);
        $mform->setDefault("operator", 0);

        $this->add_action_buttons(false, get_string("form:search", "report_coursecompletion"));
    }

    private function get_course_categories() {
        global $DB;
        $finalcategories = [];
        $allcategories = $DB->get_records("course_categories");

        $finalcategories[] = get_string("form:any_category", "report_coursecompletion");
        foreach ($allcategories as $category) {
            $finalcategories[$category->id] = $category->name;
        }

        return $finalcategories;
    }

    private function get_cohorts() {
        global $DB;
        $finalcohorts = [];
        $allcohorts = $DB->get_records("cohort");

        $finalcohorts[] = get_string("form:any_cohort", "report_coursecompletion");
        foreach ($allcohorts as $cohort) {
            $finalcohorts[$cohort->id] = $cohort->name;
        }

        return $finalcohorts;
    }
}
