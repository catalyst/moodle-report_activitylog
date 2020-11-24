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
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_activitylog\modules;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/rating/lib.php');

/**
 * Class that manages selected values as well as generates SQL for
 * the activity settings audit report.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module {

    protected $modfilters = [];
    protected $modfileareas = [];

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
    ];

    /**
     * @param $modulename
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

    public function convert_aggregate_type($value) {
        $rm = new \rating_manager();
        $options = $rm->get_aggregate_types();

        if (isset($options[$value])) {
            return $options[$value];
        }

        return $value;
    }

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

    public function convert_bool($value) {
        return $value ? get_string('true', 'report_activitylog') : get_string('false', 'report_activitylog');
    }

    public function convert_date($value) {
        return userdate($value, get_string('strftimedatetimeshort', 'langconfig'));
    }

    public function get_filters() {
        return array_merge($this->basefilters, $this->modfilters);
    }

    public function get_modfileareas() {
        return $this->modfileareas;
    }

}
