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
use local_activitysetting\reportbuilder\local\entities\scorm;
use local_activitysetting\reportbuilder\local\entities\course_module;
use local_activitysetting\reportbuilder\local\entities\grade_item;
use local_activitysetting\reportbuilder\local\entities\course_section;


/**
 * scorm settings datasource
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scorms extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('scormsetting', 'local_activitysetting');
    }

    /**
     *  Check if the datasource is enabled
     *
     * @return bool
     */
    public static function is_available(): bool {
        global $DB;

        // Check for required table.
        return $DB->get_manager()->table_exists('scorm');
    }


    /**
     * Initialize the datasource
     *
     * @return void
     */
    public function initialise(): void {

        $scormentity = new scorm();
        $scormalias = $scormentity->get_table_alias('scorm');

        $this->set_main_table('scorm', $scormalias);
        $this->add_entity($scormentity);

        // Add the course entity.
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $coursejoin = "JOIN {course} $coursealias ON $scormalias.course = $coursealias.id";
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
        $coursemodulejoin = "JOIN {course_modules} $coursemodulealias ON $scormalias.id = $coursemodulealias.instance
                            and $coursemodulealias.module = (SELECT id FROM {modules} WHERE name = 'scorm')";
        $this->add_entity($coursemoduleentity->add_join($coursemodulejoin));

        // Add the course section entity.
        $coursesectionentity = new course_section();
        $coursesectionalias = $coursesectionentity->get_table_alias('course_sections');
        $coursesectionentity->add_joins($courseentity->get_joins());
        $coursesectionentity->add_joins($coursemoduleentity->get_joins());
        $coursesectionjoin = "JOIN {course_sections} $coursesectionalias ON $coursesectionalias.id = $coursemodulealias.section
                            AND $coursesectionalias.course = $coursealias.id";
        $this->add_entity($coursesectionentity->add_join($coursesectionjoin));

        // Add the grade item entity.
        $gradeitementity = new grade_item();
        $gradeitemalias = $gradeitementity->get_table_alias('grade_items');
        $gradeitementity->add_joins($courseentity->get_joins());
        $gradeitemjoin = "LEFT JOIN {grade_items} $gradeitemalias
                            ON $gradeitemalias.iteminstance = $scormalias.id
                            AND $gradeitemalias.itemmodule = 'scorm'
                            AND $gradeitemalias.courseid = $scormalias.course";
        $this->add_entity($gradeitementity->add_join($gradeitemjoin));

        $this->add_all_from_entities();
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'course:fullname',
            'scorm:scormname',
            'course_module:groupmode',
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'course:fullname',
            'scorm:scormname',
            'course_module:groupmode',
        ];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [
            'course_category:name',
        ];
    }

    /**
     * Return the default sorting that will be added to the report once it is created
     *
     * @return array|int[]
     */
    public function get_default_column_sorting(): array {
        return [
            'scorm:scormname' => SORT_ASC,
        ];
    }
}
