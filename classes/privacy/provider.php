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
 * Privacy provider for report_activitylog.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_activitylog\privacy;

use core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy definition for report_activitylog.
 *
 * @package    report_activitylog
 * @copyright  2020 Catalyst IT {@link http://www.catalyst.net.nz}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\data_provider {

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'report_activitylog',
            [
                'activityid' => 'privacy:metadata:report_activitylog:activityid',
                'courseid' => 'privacy:metadata:report_activitylog:courseid',
                'modifierid' => 'privacy:metadata:report_activitylog:modifierid',
                'changetype' => 'privacy:metadata:report_activitylog:changetype',
                'changes' => 'privacy:metadata:report_activitylog:changes',
                'timemodified' => 'privacy:metadata:report_activitylog:timemodified'
            ],
            'privacy:metadata:report_activitylog'
        );

        $collection->add_database_table(
            'report_activitylog_settings',
            [
                'activityid' => 'privacy:metadata:report_activitylog:activityid',
                'settings' => 'privacy:metadata:report_activitylog:settings',
                'timemodified' => 'privacy:metadata:report_activitylog:timemodified'
            ],
            'privacy:metadata:report_activitylog_settings'
        );

        return $collection;
    }
}
