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
 * Renderer for activity settings audit report.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_activitysettings\activitysettings;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer class for activity settings audit report.
 *
 * @package    report_activitysettings
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_activitysettings_renderer extends plugin_renderer_base {

    /**
     * Output the module selector for the activity settings audit report.
     *
     * @param \report_activitysettings\activitysettings $report
     */
    public function print_activity_selector($report) {
        global $DB;

        $sql = "SELECT DISTINCT
            activityid
            FROM {report_activitysettings}
            WHERE changes != :initialstatus
        ";

        $params['initialstatus'] = \report_activitysettings\activitysettings::SETTINGS_INITIAL;

        if ($report->get_courseid()) {
            $sql .= " AND courseid = :courseid";
            $params['courseid'] = $report->get_courseid();
        }

        $rs = $DB->get_recordset_sql($sql, $params);
        $modules = [];
        foreach ($rs as $mod) {
            $cm = get_coursemodule_from_id('', $mod->activityid);
            if ($cm) {
                $modules[$mod->activityid] = $cm->name;
            } else {
                // Deleted module.
                $prevsettings = activitysettings::get_previous_settings($mod->activityid);
                if ($prevsettings) {
                    $modules[] = $prevsettings->name;
                }
            }
        }
        $rs->close();

        $modules = [0 => get_string('none')] + $modules;

        $select = new single_select(new moodle_url($report->get_baseurl()), 'modid', $modules, $report->get_modid(), null);
        $select->set_label(get_string('module', 'report_activitysettings'));
        echo $this->output->render($select);
    }
}