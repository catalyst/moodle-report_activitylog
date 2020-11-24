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
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_activitylog\output;

defined('MOODLE_INTERNAL') || die;

use report_activitylog\activitylog;
use table_sql;

require_once("$CFG->libdir/tablelib.php");

/**
 * Class that manages how data is displayed in the activity settings audit report.
 *
 * @package    report_activitylog
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
        // TODO: Migrate this logic to helper class.

        // Check if we need to work out the changes.
        switch ($row->changetype) {
            case activitylog::COURSE_MODULE_CREATED:
                return get_string('coursemodulecreated', 'report_activitylog');
            case activitylog::COURSE_MODULE_DELETED:
                return get_string('coursemoduledeleted', 'report_activitylog');
        }

        $stringmanager = get_string_manager();
        $changes = [];

        foreach (json_decode($row->changes) as $key => $change) {
            $str = '';

            if (!is_object($change) && !is_array($change)) {
                $key = $change;
            }

            if ($key == 'fileareas') {
                foreach ($change as $filearea) {
                    $changes[] = get_string('filesadded', 'report_activitylog', $filearea);
                }
                continue;
            }

            // Special case for advanced grading.
            if (!$str && strpos($key, 'advancedgradingmethod_') !== false) {
                $str = get_string('setting:gradingman', 'report_activitylog');
            }

            // Change to grade settings.
            if (!$str && strpos($key, 'gradepass_') !== false) {
                $str = get_string('setting:gradepass', 'report_activitylog');
            }

            $cm = get_coursemodule_from_id('', $row->activityid);
            if (!$str && str_replace('_'.$cm->modname, '', $key) == 'grade') {
                $str = get_string('setting:grade', 'report_activitylog');
            }

            // Is it defined in the report plugin?
            if (!$str && $stringmanager->string_exists('setting:'.$key, 'report_activitylog')) {
                $str = get_string('setting:'.$key, 'report_activitylog');
            }

            // Has the module defined the string?
            if (!$str && $stringmanager->string_exists($key, $cm->modname)) {
                $str = get_string($key, $cm->modname);
            }
            if (!$str && $stringmanager->string_exists('config'.$key, $cm->modname)) {
                $str = get_string('config'.$key, $cm->modname);
            }

            // Check core.
            if (!$str && $stringmanager->string_exists($key, '')) {
                $str = get_string($key);
            }

            if (!$str) {
                // Fall back to the key.
                $str = $key;
            }

            if (isset($change->updated)) {
                $change->updated = activitylog::get_formatted_value($key, $change->updated, $cm->modname);

                if (isset($change->previous)) {
                    $change->previous = activitylog::get_formatted_value($key, $change->previous, $cm->modname);

                    $str .= ' ' . get_string('valuefromto', 'report_activitylog', $change);
                } else {
                    $str .= ' ' . get_string('valueto', 'report_activitylog', $change);
                }
                $changes[$key] = $str;
            } else {
                $changes[] = get_string('updated', 'report_activitylog', $str);;
            }
        }

        if (count($changes) === 1) {
            return array_shift($changes);
        } else {
            return \html_writer::alist($changes);
        }
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
            $prevsettings = activitylog::get_previous_settings($row->activityid);

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
        if ($this->download) {
            return $row->coursename;
        }
        return \html_writer::link(course_get_url($row->courseid), $row->coursename);
    }
}
