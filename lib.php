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
 * @package    report_activitysettings
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
function report_activitysettings_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/activitysettings:view', $context)) {
        $url = new moodle_url('/report/activitysettings/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_activitysettings'), $url, navigation_node::TYPE_SETTING,
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
function report_activitysettings_coursemodule_edit_post_actions($data, $course) {
    global $USER, $DB;

    $fromform = clone $data;
    $cm = get_coursemodule_from_id('', $data->coursemodule);
    list($cm, $context, $module, $cmdata, $cw) = get_moduleinfo_data($cm, $course);

    $previoussettings = \report_activitysettings\activitysettings::get_previous_settings($data->coursemodule);

    if ($previoussettings) {

        $filter = [
            'assignfeedback_comments_commentinline',
            'gradingman',
            'introattachments',
            'revision',
            'timemodified',
            'showgradingmanagement',
            'files',
            'page_after_submit_editor',
            '',
        ];

        $fileareas = [
            'content',
            'intro',
            'introattachment',
            'package',
            'mediafile'
        ];

        $changes = [];

        // Add any missing keys for comparison.
        // We need this as empty values and some special fields are sometimes removed.
        foreach (array_merge(array_keys((array)$cmdata), array_keys((array)$previoussettings)) as $key) {
            if (!isset($previoussettings->$key)) {
                $previoussettings->$key = null;
            }
            if (!isset($fromform->$key)) {
                $fromform->$key = null;
            }
        }

        // Prepare mod page content for comparison.
        if ($cm->modname == 'page') {
            $filter[] = 'page';
            unset($previoussettings->displayoptions);
        }

        // Prepare mod quiz content for comparison.
        if ($cm->modname == 'quiz') {
            $filter[] = 'feedbacktext';
            $filter[] = 'feedbackboundaries';
            $filter[] = 'boundary_repeats';
            unset($previoussettings->displayoptions);
        }

        // Prepare mod quiz content for comparison.
        if ($cm->modname == 'workshop') {
            $filter[] = 'mform_isexpanded_id_gradingsettings';
            $filter[] = 'instructauthorseditor';
            $filter[] = 'instructreviewerseditor';
            $filter[] = 'conclusioneditor';
            unset($previoussettings->displayoptions);
        }

        // Prepare scorm content for comparison.
        if ($cm->modname == 'scorm') {
            $filter[] = 'launch';
            $filter[] = 'package';
            $filter[] = 'packagefile';
            $filter[] = 'reference';
            $fileareas[] = ['packagefile'];
            $fileareas[] = ['packagefile'];
        }

        foreach ($previoussettings as $key => $setting) {
            if (in_array($key, $filter) || strpos('mform_', $key) !== false) {
                continue;
            }

            // Ignore individual parameters in url mod.
            if ($cm->modname == 'url' &&
                    (strpos($key, 'parameter_') !== false || strpos($key, 'variable_') !== false)) {
                continue;
            }

            if (!isset($fromform->$key)) {
                if (strpos($key, 'assignfeedback_') !== false) {
                    if (!in_array('assignfeedback', $changes)) {
                        $changes[] = 'assignfeedback';
                    }
                }
            } else if (isset($fromform->$key) && $fromform->$key != $setting) {
                if ($key == 'availabilityconditionsjson') {
                    if ($setting == null) {
                        // Special case to check if availability conditions have actually changed from initial value.
                        $tree = new \core_availability\tree(json_decode($fromform->$key));
                        if (!$tree->is_empty()) {
                            $changes[] = $key;
                        }
                    } else {
                        $changes[] = $key;
                    }
                } else {
                    $changes[] = $key;
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
                if (!in_array($file->get_filearea(), $changes)) {
                    if ($file->get_component() == 'mod_scorm') {
                        // SCORM timemodified always gets updated. Use time created.
                        if ($file->get_timecreated() > $previoussettings->timemodified) {
                            $changes[] = $file->get_filearea();
                        }
                    } else {
                        $changes[] = $file->get_filearea();
                    }
                }
            }
        }

        // Log any changes.
        if ($changes) {
            $log = (object)[
                'activityid' => $data->coursemodule,
                'courseid' => $course->id,
                'modifierid' => $USER->id,
                'changes' => json_encode($changes),
                'settings' => json_encode($data),
                'timemodified' => $data->timemodified ?? time(),
            ];

            $DB->insert_record('report_activitysettings', $log);
        }
    }

    return $data;
}
