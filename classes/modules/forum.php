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

require_once($CFG->dirroot.'/mod/forum/lib.php');

/**
 * Class that manages selected values as well as generates SQL for
 * the activity settings audit report.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum extends module {

    public function get_value($setting, $value) {
        switch ($setting) {
            case 'displaywordcount':
            case 'forcesubscribe':
            case 'printintro':
                return $this->convert_bool($value);
            case 'assesstimefinish':
            case 'assesstimestart':
                return $this->convert_date($value);
            case 'trackingtype':
                return $this->convert_trackingtype_value($value);
            case 'assessed':
                return $this->convert_aggregate_type($value);
        }
        return parent::get_value($setting, $value);
    }

    private function convert_trackingtype_value($value) {
        switch ($value) {
            case FORUM_TRACKING_OPTIONAL:
                return get_string('trackingoptional', 'forum');
            case FORUM_TRACKING_OFF:
                return get_string('trackingoff', 'forum');
            case FORUM_TRACKING_FORCED:
                return get_string('trackingon', 'forum');
        }

        return $value;
    }
}
