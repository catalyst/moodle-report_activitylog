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
 * Event observer for report_activitysettings.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for report_activitysettings.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_activitysettings_observer {

    /**
     * Triggered via course_module_created event.
     *
     * @param \core\event\course_module_created $event
     * @return bool true on success.
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $DB;

        $cm = get_coursemodule_from_id('', $event->objectid);
        $course = get_course($cm->course);

        list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($cm, $course);

        $log = (object)[
            'activityid' => $event->objectid,
            'courseid' => $course->id,
            'modifierid' => $event->userid,
            'changes' => \report_activitysettings\activitysettings::COURSE_MODULE_CREATED,
            'settings' => json_encode($data),
            'timemodified' => $event->timecreated,
        ];

        $DB->insert_record('report_activitysettings', $log);

        return true;
    }

    /**
     * Triggered via course_module_deleted event.
     *
     * @param \core\event\course_module_deleted $event
     * @return bool true on success.
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;

        $log = (object)[
            'activityid' => $event->objectid,
            'courseid' => $event->courseid,
            'modifierid' => $event->userid,
            'changes' => \report_activitysettings\activitysettings::COURSE_MODULE_DELETED,
            'settings' => null,
            'timemodified' => $event->timecreated,
        ];

        $DB->insert_record('report_activitysettings', $log);

        return true;
    }

}
