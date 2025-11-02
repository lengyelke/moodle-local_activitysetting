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

declare(strict_types=1);

namespace local_activitysetting\reportbuilder\datasource;

use core_reportbuilder_generator;
use core_reportbuilder\manager;
use local_activitysetting\reportbuilder\datasource\scorms;
use core_reportbuilder\local\filters\{category, select, text};
use core_reportbuilder\tests\core_reportbuilder_testcase;

/**
 * Tests for Activity Setting Report
 *
 * @package    local_activitysetting
 * @category   test
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_activitysetting\reportbuilder\datasource\scorms
 */
final class scorms_test extends core_reportbuilder_testcase {
    /**
     * Test default datasource
     *
     * @return void
     * @covers ::datasource_default
     */
    public function test_datasource_default(): void {
        global $CFG, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category(['name' => 'Zoo', 'idnumber' => 'Z01']);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);

        $scorm1 = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id]);
        $scorm2 = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id]);
        $scorm3 = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id]);

        /** @var core_reportbuilder_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $report = $generator->create_report(['name' => 'My report', 'source' => scorms::class, 'default' => 1]);

        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(3, $content);

        // Default columns are course fullname, scormname, groupmode.
        // Sorted by name ascending.
        $this->assertEquals([
            [$course->fullname, $scorm1->name, get_string('groupsnone', 'core')],
            [$course->fullname, $scorm2->name, get_string('groupsnone', 'core')],
            [$course->fullname, $scorm3->name, get_string('groupsnone', 'core')],
        ], array_map('array_values', $content));
    }

     /**
      * Test datasource columns that aren't added by default
      *
      * @return void
      * @covers ::datasource_non_default_columns
      */
    public function test_datasource_non_default_columns(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category(['name' => 'Zoo', 'idnumber' => 'Z01']);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id, 'enablecompletion' => 1]);
        $scorm1 = $this->getDataGenerator()->create_module('scorm', [
            'course' => $course->id,
            'name' => 'Scorm 1',
            'maxattempt' => 1,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
        ]);
        $scorm2 = $this->getDataGenerator()->create_module('scorm', [
            'course' => $course->id,
            'name' => 'Scorm 2',
            'maxattempt' => 2,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $scorm3 = $this->getDataGenerator()->create_module('scorm', [
            'course' => $course->id,
            'name' => 'Scorm 3',
            'maxattempt' => 3,
            'completion' => COMPLETION_TRACKING_NONE,
        ]);

        /** @var core_reportbuilder_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $report = $generator->create_report(['name' => 'My report', 'source' => scorms::class, 'default' => 0]);

        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'scorm:scormname']);
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'scorm:scormattempts']);
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'course_module:completion']);
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'course_module:completionview']);

        $generator->create_condition([
            'reportid' => $report->get('id'),
            'uniqueidentifier' => 'course_module:completion',
        ]);

        // Load report instance, set condition.
        $instance1 = manager::get_report_from_persistent($report);
        $instance1->set_condition_values([
            'course_module:completion_operator' => select::EQUAL_TO,
            'course_module:completion_value' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $content1 = $this->get_custom_report_content($report->get('id'));

        $this->assertCount(1, $content1);

        // The columns are: scormname, maxattempts, completion, completionview.
        // Sorted by name ascending.
        $this->assertEquals([
            [$scorm1->name, get_string('attempt1', 'scorm'), get_string('completion_automatic', 'completion'), get_string('yes')],
        ], array_map('array_values', $content1));

        // Load report instance, set condition.
        $instance2 = manager::get_report_from_persistent($report);
        $instance2->set_condition_values([
            'course_module:completion_operator' => select::EQUAL_TO,
            'course_module:completion_value' => COMPLETION_TRACKING_MANUAL,
        ]);

        $content2 = $this->get_custom_report_content($report->get('id'));

        $this->assertCount(1, $content2);

        // The columns are: scormname, maxattempts, completion, completionview.
        // Sorted by name ascending.
        $this->assertEquals([
            [$scorm2->name, get_string('attemptsx', 'scorm', 2), get_string('completion_manual', 'completion'), get_string('no')],
        ], array_map('array_values', $content2));

        // Load report instance, set condition.
        $instance3 = manager::get_report_from_persistent($report);
        $instance3->set_condition_values([
            'course_module:completion_operator' => select::EQUAL_TO,
            'course_module:completion_value' => COMPLETION_TRACKING_NONE,
        ]);

        $content3 = $this->get_custom_report_content($report->get('id'));

        $this->assertCount(1, $content3);

        // The columns are: scormname, maxattempts, completion, completionview.
        // Sorted by name ascending.
        $this->assertEquals([
            [$scorm3->name, get_string('attemptsx', 'scorm', 3), get_string('completion_none', 'completion'), get_string('no')],
        ], array_map('array_values', $content3));
    }


    /**
     * Stress test datasource
     *
     * In order to execute this test PHPUNIT_LONGTEST should be defined as true in phpunit.xml or directly in config.php
     *
     * @return void
     * @covers ::stress_datasource
     */
    public function test_stress_datasource(): void {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        $this->resetAfterTest();
        // Ensure a current user exists for scorm generator.
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category(['name' => 'Zoo', 'idnumber' => 'Z01']);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $scorm = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id]);

        $this->datasource_stress_test_columns(scorms::class);
        $this->datasource_stress_test_columns_aggregation(scorms::class);
        $this->datasource_stress_test_conditions(scorms::class, 'scorm:scormname');
    }
}
