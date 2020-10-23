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
 * Ad hoc task definition for populating initial log values.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_activitysettings\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/modlib.php');

/**
 * Ad hoc task definition for populating initial log values.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class populate_activitysettings_log_table extends \core\task\adhoc_task {

    /**
     * Gets all the current settings for course modules and adds to the log table.
     *
     * It's done as a task as we can't use these methods during an upgrade.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function execute() {
        global $DB;

        // Populate the log table with current status.
        // This is needed as a point of comparison when there's a new change.
        // The initial record gets stored with a change status that won't appear in the report.
        $rs = $DB->get_recordset('course_modules');

        foreach ($rs as $record) {
            $cm = get_coursemodule_from_id('', $record->id);
            $course = get_course($cm->course);

            list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($cm, $course);

            $log = (object)[
                'activityid' => $record->id,
                'courseid' => $course->id,
                'modifierid' => '0',
                'changes' => \report_activitysettings\activitysettings::SETTINGS_INITIAL,
                'settings' => json_encode($data),
                'timemodified' => $data->timemodified,
            ];

            $DB->insert_record('report_activitysettings', $log);
        }

        $rs->close();
    }

}
