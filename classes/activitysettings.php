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
 * Manages the data for the activity settings audit report.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_activitysettings;

defined('MOODLE_INTERNAL') || die;

use context;

/**
 * Class that manages selected values as well as generates SQL for
 * the activity settings audit report.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitysettings {

    const SETTINGS_INITIAL = 0;
    const COURSE_MODULE_CREATED = 1;
    const COURSE_MODULE_DELETED = 2;

    /** @var context context of the report */
    protected $context;

    /** @var int course id to filter results by */
    protected $courseid = 0;

    /** @var int user id to filter results by */
    protected $modid = 0;

    /** @var \moodle_url baseurl of the report */
    protected $baseurl;

    /** @var array parameters for filtering the report */
    protected $params;

    /**
     * Set up the activity settings class.
     *
     * @param object $course course object if we are in course level view.
     * @param context $context context the report is running in.
     * @param int $modid mod id if report is filtered by user.
     * @param \moodle_url $baseurl base url for the report.
     */
    public function __construct($course, $context, $modid, $baseurl) {
        $this->courseid = $course ? $course->id : 0;
        $this->modid = $modid;
        $this->context = $context;
        $this->baseurl = $baseurl;
    }

    /**
     * Get the columns needed for the report table.
     *
     * @return string[]
     */
    public function get_columns() {
        if ($this->courseid) {
            // Exclude the course column.
            return [
                'module',
                'changes',
                'modifierid',
                'timemodified'
            ];
        }
        return [
            'module',
            'coursename',
            'changes',
            'modifierid',
            'timemodified'
        ];
    }

    /**
     * Get the headers for the table that match the order from get_columns.
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_headers() {
        if ($this->courseid) {
            // Exclude the course column.
            return [
                get_string('module', 'report_activitysettings'),
                get_string('changes', 'report_activitysettings'),
                get_string('changedby', 'report_activitysettings'),
                get_string('timemodified', 'report_activitysettings'),
            ];
        }
        return [
            get_string('module', 'report_activitysettings'),
            get_string('course'),
            get_string('changes', 'report_activitysettings'),
            get_string('changedby', 'report_activitysettings'),
            get_string('timemodified', 'report_activitysettings'),
        ];
    }

    /**
     * Gets the fields to SELECT for the SQL query.
     *
     * @return string
     */
    public function get_fields_sql() {
        return "
            ra.id,
            courseid,
            activityid,
            c.fullname AS coursename,
            ra.changes,
            modifierid,
            ra.timemodified
        ";
    }

    /**
     * Fetches the FROM SQL for the query.
     *
     * @return string
     */
    public function get_from_sql() {
        return "{report_activitysettings} ra
                    JOIN {course} c ON c.id = ra.courseid";
    }

    /**
     * Get the params based on any filters that have been set.
     * Should only be called after get_where_sql.
     *
     * @return array
     */
    public function get_params() {
        return $this->params;
    }

    /**
     * Gets the WHERE clause and sets up report parameters.
     *
     * @return string
     */
    public function get_where_sql() {
        $where = "ra.changes != :initialrecord";
        $this->params['initialrecord'] = self::SETTINGS_INITIAL;

        if ($this->courseid) {
            $where .= " AND c.id = :courseid";
            $this->params['courseid'] = $this->courseid;
        }

        if ($this->modid) {
            $where .= " AND ra.activityid = :modid";
            $this->params['modid'] = $this->modid;
        }

        return $where;
    }

    /**
     * Getter for baseurl.
     *
     * @return \moodle_url
     */
    public function get_baseurl() {
        return $this->baseurl;
    }

    /**
     * Getter for context.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Getter for courseid.
     *
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Getter for modid.
     *
     * @return int
     */
    public function get_modid() {
        return $this->modid;
    }

    /**
     * Generates and returns the filename for report downloads.
     *
     * @return string
     */
    public function get_filename() {
        return 'activitysettingsaudit_' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
    }

    /**
     * Get the previous status for a course module.
     *
     * @return array|false
     */
    public static function get_previous_settings($activityid) {
        global $DB;

        $sql = "
            SELECT
                id,
                settings,
                timemodified
            FROM {report_activitysettings}
            WHERE activityid = :activityid
                AND changes != :deleted
            ORDER BY timemodified DESC;
        ";

        $params = [
            'activityid' => $activityid,
            'deleted' => self::COURSE_MODULE_DELETED
        ];

        $records = $DB->get_records_sql($sql, $params);

        $previous = array_shift($records);
        if ($previous) {
            $settings = json_decode($previous->settings);
            $settings->timemodified = $previous->timemodified;
            return $settings;
        }

        return false;
    }

}
