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
 * Table definition for the activity settings audit report.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_activitysettings\output;

defined('MOODLE_INTERNAL') || die;

use report_activitysettings\activitysettings;
use table_sql;

require_once("$CFG->libdir/tablelib.php");

/**
 * Class that manages how data is displayed in the activity settings audit report.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_table extends table_sql {

    /**
     * Format the timemodified cell.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_timemodified($row) {
        return userdate($row->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Format the changes cell to show what the update was.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_changes($row) {

        // Check if we need to work out the changes.
        switch ($row->changes) {
            case activitysettings::COURSE_MODULE_CREATED:
                return get_string('coursemodulecreated', 'report_activitysettings');
            case activitysettings::COURSE_MODULE_DELETED:
                return get_string('coursemoduledeleted', 'report_activitysettings');
        }

        $stringmanager = get_string_manager();
        $changes = [];
        $assignmentfeedbackchanged = false;
        $assignmentsubmissionchanged = false;
        foreach (json_decode($row->changes) as $change) {

            // Special case for advanced grading.
            if (strpos($change, 'advancedgradingmethod_') !== false) {
                $changes[] = get_string('setting:gradingman', 'report_activitysettings');
                continue;
            }

            // Change to assignment feedback settings.
            if (strpos($change, 'assignfeedback_') !== false) {
                $assignmentfeedbackchanged = true;
                continue;
            }
            // Change to assignment submission settings.
            if (strpos($change, 'assignsubmission_') !== false) {
                $assignmentsubmissionchanged = true;
                continue;
            }
            // Change to grade settings.
            if (strpos($change, 'gradepass_') !== false) {
                get_string('setting:gradepass', 'report_activitysettings');
                continue;
            }

            $cm = get_coursemodule_from_id('', $row->activityid);
            if (str_replace('_'.$cm->modname, '', $change) == 'grade') {
                $changes[] = get_string('setting:grade', 'report_activitysettings');
                continue;
            }

            // Is it defined in the report plugin?
            if ($stringmanager->string_exists('setting:'.$change, 'report_activitysettings')) {
                $changes[] = get_string('setting:'.$change, 'report_activitysettings');
                continue;
            }

            // Has the module defined the string?
            if ($stringmanager->string_exists($change, $cm->modname)) {
                $changes[] = get_string($change, $cm->modname);
                continue;
            }
            if ($stringmanager->string_exists('config'.$change, $cm->modname)) {
                $changes[] = get_string('config'.$change, $cm->modname);
                continue;
            }

            // Check core.
            if ($stringmanager->string_exists($change, '')) {
                $changes[] = get_string($change);
                continue;
            }

            // Fall back to the key.
            $changes[] = $change;
        }

        if ($assignmentfeedbackchanged) {
            $changes[] = get_string('setting:assignfeedback', 'report_activitysettings');
        }
        if ($assignmentsubmissionchanged) {
            $changes[] = get_string('setting:assignsubmission', 'report_activitysettings');
        }

        return \html_writer::alist($changes);
    }

    /**
     * Format the modifierid cell. Gets fullname of user making the change.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_modifierid($row) {
        global $DB;

        return fullname($DB->get_record('user', ['id' => $row->modifierid]));
    }

    /**
     * Format the module cell. Gets the name of the module.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_module($row) {
        $cm = get_coursemodule_from_id('', $row->activityid);

        if ($cm) {
            return $cm->name;
        } else {
            // Module likely deleted, try to get last value from logs.
            $prevsettings = activitysettings::get_previous_settings($row->activityid);

            if ($prevsettings) {
                return $prevsettings->name;
            }
        }

        return '';
    }

    /**
     * Format the coursename cell. Generates a link to filter by course.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_coursename($row) {
        return \html_writer::link(
            new \moodle_url('/report/activitysettings/index.php', ['id' => $row->courseid]),
            $row->coursename
        );
    }
}
