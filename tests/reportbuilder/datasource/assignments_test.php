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
use core_course_category;
use local_activitysetting\reportbuilder\datasource\assignments;
use core_reportbuilder\local\filters\{category, select, text};
use core_reportbuilder\tests\core_reportbuilder_testcase;

/**
 * Tests for Activity Setting Report
 *
 * @package    local_activitysetting
 * @category   test
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignments_test extends core_reportbuilder_testcase {

    /**
     * Test default datasource
     */
    public function test_datasource_default(): void {
        global $CFG;
        $this->resetAfterTest();

        $category = $this->getDataGenerator()->create_category(['name' => 'Zoo', 'idnumber' => 'Z01']);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);

        $assignment1 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $assignment2 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $assignment3 = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        /** @var core_reportbuilder_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $report = $generator->create_report(['name' => 'My report', 'source' => assignments::class, 'default' => 1]);

        $content = $this->get_custom_report_content($report->get('id'));
        $this->assertCount(3, $content);

        // Default columns are fullname, urlastext, assignmentname, duedate, gradetype, attemptreopenmethod, groupmode.
        // Sorted by name ascending.
        $this->assertEquals([
            [$course->fullname, $assignment1->name, '', get_string('modgradetypepoint', 'grades'),
                get_string('attemptreopenmethod_untilpass', 'mod_assign'), get_string('groupsnone', 'core')],
            [$course->fullname, $assignment2->name, '', get_string('modgradetypepoint', 'grades'),
            get_string('attemptreopenmethod_untilpass', 'mod_assign'), get_string('groupsnone', 'core')],
            [$course->fullname, $assignment3->name, '', get_string('modgradetypepoint', 'grades'),
            get_string('attemptreopenmethod_untilpass', 'mod_assign'), get_string('groupsnone', 'core')],
        ], array_map('array_values', $content));
    }

    /**
     * Stress test datasource
     *
     * In order to execute this test PHPUNIT_LONGTEST should be defined as true in phpunit.xml or directly in config.php
     */
    public function test_stress_datasource(): void {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        $this->resetAfterTest();

        $category = $this->getDataGenerator()->create_category(['name' => 'Zoo', 'idnumber' => 'Z01']);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        $this->datasource_stress_test_columns(assignments::class);
        $this->datasource_stress_test_columns_aggregation(assignments::class);
        $this->datasource_stress_test_conditions(assignments::class, 'assignment:assignmentname');
    }
}
