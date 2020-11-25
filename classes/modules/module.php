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
 * Helper for specific modules that require workarounds. I.e.
 * filtering of fields that we don't want to track.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_activitylog\modules;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/rating/lib.php');

/**
 * Base class to manage filtered fields/file areas for modules.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module {

    /**
     * @var array module specific filters.
     */
    protected $modfilters = [];
    /**
     * @var array module specific fileareas.
     */
    protected $modfileareas = [];

    /**
     * @var string[] filters to apply to all modules. Supports '*' as a wildcard.
     */
    private $basefilters = [
        'id',
        'timemodified',
        'introeditor',

        // Standard form fields.
        'completionunlocked',
        'course',
        'coursemodule',
        'section',
        'module',
        'modulename',
        'instance',
        'add',
        'update',
        'return',
        'sr',
        'submitbutton',

        // Ignore legacyfiles.
        'legacyfiles',
        'legacyfileslast',

        // Don't compare complex objects.
        '_advancedgradingdata',
        'displayoptions',
        'gradingman',

        // Wildcard exclusions.
        'mform_*',
    ];

    /**
     * @var string[] fileares to apply to all modules.
     */
    private $basefileareas = [
        'content',
        'intro',
        'introattachment',
        'package',
        'mediafile'
    ];

    /**
     * Helper method to get the module class by mod name.
     *
     * @param string $modulename name of module (i.e. 'wiki')
     * @return module
     */
    public static function get_module($modulename) {
        $class = 'report_activitylog\modules\\';
        if (class_exists('\\' . $class . $modulename)) {
            $class .= $modulename;
        } else if (in_array($modulename, \report_activitylog\modules\resource_module::RESOURCES)) {
            $class .= 'resource_module';
        } else {
            $class .= 'module';
        }

        return new $class();
    }

    /**
     * Formatter method that takes a setting and value and converts
     * it into a date/string representation of a bool etc.
     *
     * @param string $setting name of the field
     * @param string $value the setting's value to convert
     * @return string
     * @throws \moodle_exception
     */
    public function get_value($setting, $value) {
        switch ($setting) {
            case 'competency_rule':
                return \core_competency\course_module_competency::get_ruleoutcome_name($value);
            case 'groupmode':
                return $this->convert_groupmode($value);
            case 'showdate':
            case 'showsize':
            case 'showtype':
            case 'showdescription':
            case 'printintro':
            case 'visible':
                return $this->convert_bool($value);
        }

        if (is_object($value) || is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Converts int representation of aggregate to string
     * representation.
     *
     * @param int $value
     * @return string
     */
    public function convert_aggregate_type($value) {
        $rm = new \rating_manager();
        $options = $rm->get_aggregate_types();

        if (isset($options[$value])) {
            return $options[$value];
        }

        return $value;
    }

    /**
     * Converts int representation of groupmode to string.
     * representation.
     *
     * @param int $value
     * @return string
     */
    public function convert_groupmode($value) {
        switch ($value) {
            case NOGROUPS:
                return get_string('groupsnone', 'group');
            case SEPARATEGROUPS:
                return get_string('groupsseparate', 'group');
            case VISIBLEGROUPS:
                return get_string('groupsvisible', 'group');
        }

        return $value;
    }

    /**
     * Converts boolean to string representation of true/false.
     *
     * @param bool $value
     * @return string
     */
    public function convert_bool($value) {
        return $value ? get_string('true', 'report_activitylog') : get_string('false', 'report_activitylog');
    }

    /**
     * Converts timestamp to human readable date.
     *
     * @param bool $value
     * @return string
     */
    public function convert_date($value) {
        return userdate($value, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Gets array of filtered fields. Combines the base filters and
     * any filters defined by the module as well.
     *
     * @return array
     */
    public function get_filters() {
        return array_merge($this->basefilters, $this->modfilters);
    }

    /**
     * Gets array of fileareas. Combines the base fileareas and
     * any fileareas defined by the module as well.
     *
     * @return array
     */
    public function get_fileareas() {
        return array_merge($this->basefileareas, $this->modfileareas);
    }

    /**
     * Takes an array of fields and returns a copy of the array
     * without any fields that are filtered.
     *
     * @param array $fields
     * @return array
     */
    public function remove_filtered_fields($fields) {
        $filters = $this->get_filters();
        $filteredfields = array_diff($fields, $filters);

        // Check wildcard filter values.
        foreach ($filters as $filter) {
            if (strpos($filter, '*') === false) {
                continue;
            }

            $pattern = '/' . str_replace('\\', '\\\\', str_replace('*', '.*', $filter)) . '/';

            foreach ($filteredfields as $key => $filteredfield) {
                if (preg_match($pattern, $filteredfield)) {
                    unset($filteredfields[$key]);
                }
            }
        }

        return $filteredfields;
    }
}
