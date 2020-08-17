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
 * Privacy Subsystem implementation for report_coursecompletion.
 *
 * @package    report_coursecompletion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_coursecompletion\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for report_coursecompletion implementing null_provider.
 *
 * @copyright 	Catalyst IT {@link http://catalyst.net.nz}
 * @author 	bO Pierce 
 * @contributor Michael Nixon <michael.nixon@catalyst.net.nz>
 * @license    	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {
    // This plugin does not store any personal user data.
    use \core_privacy\local\legacy_polyfill;

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * This function is compatible with old php version.
     * Diff is the underscore '_' in the beginning.
     * But get_reason() still works 
     * because of the trait legacy_polyfill.
     *
     * @return  string
     */
    public static function _get_reason() {
        return 'privacy:no_userid_data';
    }
}
