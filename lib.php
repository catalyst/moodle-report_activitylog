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
 * Callbacks for activity settings audit report.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Allow report to show in course contexts.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 */
function report_activitylog_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/activitylog:view', $context)) {
        $url = new moodle_url('/report/activitylog/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_activitylog'), $url, navigation_node::TYPE_SETTING,
            null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Go over the settings for a course module and determine if any
 * have changed. Updates the log if any changes were made.
 *
 * @param stdClass $data Data from the form submission.
 * @param stdClass $course The course.
 */
function report_activitylog_coursemodule_edit_post_actions($data, $course) {
    // TODO: Migrate logic into helper class.
    $newsettings = clone $data;

    $cm = get_coursemodule_from_id('', $data->coursemodule);
    $previoussettings = \report_activitylog\activitylog::get_previous_settings($data->coursemodule);
    $modulehelper = \report_activitylog\modules\module::get_module($cm->modname);

    // Add any missing keys for comparison.
    // We need this as empty values and some special fields are sometimes removed.
    list($cm, $context, $module, $cmdata, $cw) = get_moduleinfo_data($cm, $course);

    $fields = array_unique(
        array_merge(
            array_keys((array)$cmdata),
            array_keys((array)$previoussettings),
            array_keys((array)$newsettings)
        )
    );

    // Clean fields that we don't need from the submitted data.
    $filter = $modulehelper->get_filters();
    unset($newsettings->displayoptions);

    foreach ($fields as $key => $field) {
        if (in_array($field, $filter) || strpos($field, 'mform_') !== false) {
            unset($newsettings->$field);
            unset($fields[$key]);
            continue;
        }

        if (!array_key_exists($field, $newsettings) && array_key_exists($field, $cmdata)) {
            $newsettings->$field = $cmdata->$field;
        }
    }

    if ($previoussettings) {

        $fileareas = [
            'content',
            'intro',
            'introattachment',
            'package',
            'mediafile'
        ];
        $changes = [];

        foreach ($fields as $field) {

            // Ignore individual parameters in url mod.
            if ($cm->modname == 'url'
                    && (strpos($field, 'parameter_') !== false || strpos($field, 'variable_') !== false)) {
                unset($newsettings->$field);
                continue;
            }

            if (!array_key_exists($field, $previoussettings)) {
                if (array_key_exists($field, $newsettings)) {
                    $changes[$field] = [
                        'updated' => $newsettings->$field
                    ];
                }

                continue;
            }

            $previoussetting = array_key_exists($field, $previoussettings) ? $previoussettings->$field : null;

            if (array_key_exists($field, $newsettings) && $newsettings->$field != $previoussetting) {
                if ($field == 'availabilityconditionsjson') {
                    if (is_null($previoussetting)) {
                        // Special case to check if availability conditions have actually changed from initial value.
                        $tree = new \core_availability\tree(json_decode($newsettings->$field));
                        if (!$tree->is_empty()) {
                            $changes[] = $field;
                        }
                    } else {
                        $changes[] = $field;
                    }
                } else {
                    if (!is_object($newsettings->$field) && !is_array($newsettings->$field)) {
                        $change = ['updated' => $newsettings->$field];
                        if (!empty($previoussetting)) {
                            $change['previous'] = $previoussetting;
                        }
                        $changes[$field] = $change;
                    } else {
                        $changes[] = $field;
                    }
                }
            }
        }

        // Check fileareas for updates.
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $updatedfiles = $fs->get_area_files(
            $context->id,
            'mod_'.$cm->modname,
            $fileareas,
            false,
            "filearea, timemodified DESC",
            false,
            $previoussettings->timemodified
        );

        if ($updatedfiles) {
            foreach ($updatedfiles as $file) {
                if (!isset($changes['fileareas']) || !in_array($file->get_filearea(), $changes['fileareas'])) {
                    if ($file->get_component() == 'mod_scorm') {
                        // SCORM timemodified always gets updated. Use time created.
                        if ($file->get_timecreated() > $previoussettings->timemodified) {
                            $changes['fileareas'][] = $file->get_filearea();
                        }
                    } else {
                        $changes['fileareas'][] = $file->get_filearea();
                    }
                }
            }
        }

        // Log any changes.
        if ($changes) {
            \report_activitylog\activitylog::log_changes(
                $data->coursemodule,
                $course->id,
                $changes,
                $newsettings
            );
        }
    } else {
        \report_activitylog\activitylog::log_changes(
            $data->coursemodule,
            $course->id,
            [],
            $newsettings
        );
    }

    return $data;
}
