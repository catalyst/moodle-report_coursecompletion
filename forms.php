<?php
    defined("MOODLE_INTERNAL") || die;

    require_once __DIR__ . "/../../config.php";
    require_once $CFG->libdir . "/formslib.php";

class ReportForm extends moodleform
{
    public function definition()
    {
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

        private function get_course_categories() {
            global $DB;
            $final_categories = [];
            $all_categories = $DB->get_records("course_categories");

            $final_categories[] = get_string("form:any_category", "report_coursecompletion");
            foreach($all_categories as $category) {
                $final_categories[$category->id] = $category->name;
            }

            return $final_categories;
        }

        private function get_cohorts() {
            global $DB;
            $final_cohorts = [];
            $all_cohorts = $DB->get_records("cohort");

            $final_cohorts[] = get_string("form:any_cohort", "report_coursecompletion");
            foreach($all_cohorts as $cohort) {
                $final_cohorts[$cohort->id] = $cohort->name;
            }

            return $final_cohorts;
        }
    }
?>
