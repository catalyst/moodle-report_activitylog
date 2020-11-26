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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir. '/completionlib.php');
require_once($CFG->dirroot. '/course/tests/courselib_test.php');

class report_activitylog_testcase extends advanced_testcase {

    public function test_enrolment_created_records() {
        global $DB;
        $this->resetAfterTest();

        // Use the private methods within the course test case to create course modules.
        $courselibtestcase = new core_course_courselib_testcase();
        $reflector = new ReflectionObject($courselibtestcase);
        $modtestmethod = $reflector->getMethod('create_specific_module_test');
        $modtestmethod->setAccessible(true);

        // Create an assign module.
        $modinfo = $modtestmethod->invoke($courselibtestcase, 'assign');

        $cm = get_coursemodule_from_id('assign', $modinfo->coursemodule, 0, false, MUST_EXIST);
        $course = get_course($cm->course);

        list($cm, $context, $module, $cmdata, $cw) = get_moduleinfo_data($cm, $course);

        // Ensure we have a record in each table as expected.
        $this->assertEquals(1, $DB->count_records('report_activitylog', ['activityid' => $modinfo->coursemodule]));
        $this->assertEquals(1, $DB->count_records('report_activitylog_settings', ['activityid' => $modinfo->coursemodule]));

        // Update the module.
        $cmdata->name = 'testupdate';
        $cmdata->cmidnumber = 'newidnumber';
        $cmdata->mform_form_element = 'Form element. Should be ignored';
        $cmdata->submitbutton = 'Form element. Should be ignored';
        report_activitylog_coursemodule_edit_post_actions($cmdata, $course);

        // There should now be 2 logs and one settings record.
        $this->assertEquals(2, $DB->count_records('report_activitylog', ['activityid' => $modinfo->coursemodule]));
        $this->assertEquals(1, $DB->count_records('report_activitylog_settings', ['activityid' => $modinfo->coursemodule]));

        // Get the latest log and ensure the change has been picked up.
        $activityupdated = $DB->get_record('report_activitylog', [
            'activityid' => $modinfo->coursemodule,
            'changetype' => \report_activitylog\activitylog::COURSE_MODULE_UPDATED
        ]);

        $changes = (array)json_decode($activityupdated->changes);

        // Check that we tracked only 2 changes.
        $this->assertCount(2, $changes);

        // Check that the name field tracks a before and after value.
        $this->assertTrue(isset($changes['name']->updated));
        $this->assertTrue(isset($changes['name']->previous));
        $this->assertEquals('testupdate', $changes['name']->updated);

        // Check that the idnumber field tracks a before and after value.
        $this->assertTrue(isset($changes['cmidnumber']->updated));
        $this->assertTrue(isset($changes['cmidnumber']->previous));
        $this->assertEquals('newidnumber', $changes['cmidnumber']->updated);

    }

}
