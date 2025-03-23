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

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_course\reportbuilder\local\entities\course_category;
use local_activitysetting\reportbuilder\local\entities\assignment;
use local_activitysetting\reportbuilder\local\entities\course_module;

/**
 * Assignmnent settings datasource
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignments extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('assignmentsetting', 'local_activitysetting');
    }

    /**
     *  Check if the datasource is enabled
     *
     * @return bool
     */
    public static function is_available(): bool {
        global $DB;

        // Check for required table.
        return $DB->get_manager()->table_exists('assign');
    }


    /**
     * Initialize the datasource
     *
     * @return void
     */
    public function initialise(): void {

        $assignmententity = new assignment();
        $assignalias = $assignmententity->get_table_alias('assign');

        $this->set_main_table('assign', $assignalias);
        $this->add_entity($assignmententity);

        // Add the course entity.
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $coursejoin = "JOIN {course} $coursealias ON $assignalias.course = $coursealias.id";
        $this->add_entity($courseentity->add_join($coursejoin));

        // Join the course category entity.
        $coursecatentity = new course_category();
        $coursecatalias = $coursecatentity->get_table_alias('course_categories');
        $coursecatentity->add_joins($courseentity->get_joins());
        $coursecatjoin = "JOIN {course_categories} {$coursecatalias} ON {$coursecatalias}.id = {$coursealias}.category";
        $this->add_entity($coursecatentity->add_join($coursecatjoin));

        // Add the course module entity.
        $coursemoduleentity = new course_module();
        $coursemodulealias = $coursemoduleentity->get_table_alias('course_modules');
        $coursemoduleentity->add_joins($courseentity->get_joins());
        $coursemodulejoin = "JOIN {course_modules} $coursemodulealias ON $assignalias.id = $coursemodulealias.instance
                            and $coursemodulealias.module = (SELECT id FROM {modules} WHERE name = 'assign')";
        $this->add_entity($coursemoduleentity->add_join($coursemodulejoin));

        $this->add_all_from_entities();
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }
}
