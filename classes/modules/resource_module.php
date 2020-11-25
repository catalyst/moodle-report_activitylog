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

require_once($CFG->libdir.'/resourcelib.php');

/**
 * Base class to manage filtered fields/file areas for 'resource' activities.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resource_module extends module {

    const RESOURCES = [
        'book',
        'file',
        'folder',
        'imscp',
        'label',
        'page',
        'resource',
        'url',
    ];

    protected $modfilters = [
        'files',
        'package',
        'page',
        'revision',
    ];

    public function get_value($setting, $value) {
        switch ($setting) {
            case 'displaywordcount':
            case 'forcesubscribe':
                return $this->convert_bool($value);
            case 'filterfiles':
                return $this->convert_filterfiles_value($value);
            case 'display':
                return $this->convert_display_value($value);
        }

        return parent::get_value($setting, $value);
    }

    protected function convert_filterfiles_value($value) {
        switch ($value) {
            case '0':
                return get_string('none');
            case '1':
                return get_string('allfiles');
            case '2':
                return get_string('htmlfilesonly');
        }

        return $value;
    }

    protected function convert_display_value($value) {
        $options = resourcelib_get_displayoptions(explode(',', get_config('resource')->displayoptions));

        if (isset($options[$value])) {
            return $options[$value];
        }

        return $value;
    }
}
