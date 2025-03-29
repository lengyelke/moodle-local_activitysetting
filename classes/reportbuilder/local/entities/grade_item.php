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

namespace local_activitysetting\reportbuilder\local\entities;

use context_course;
use context_helper;
use context_system;
use context_user;
use core\context;
use core_component;
use core_date;
use html_writer;
use lang_string;
use moodle_url;
use stdClass;
use core_reportbuilder\local\filters\{boolean_select, date, duration, number, text, select};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\helpers\custom_fields;

/**
 * Class grade_item
 *
 * This class represents a grade item entity in the report builder.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_item extends base {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'garde_items',
            'scale',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('gradeitemsetting', 'local_activitysetting');
    }

    /**
     * Initialise the entity.
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        $conditions = $this->get_all_filters();
        foreach ($conditions as $condition) {
            $this->add_condition($condition);
        }

        return $this;

    }
    /**
     * Returns list of all available columns
     *
     * These are all the columns available to use in any report that uses this entity.
     *
     * @return columns[]
     */
    protected function get_all_columns(): array {

        global $DB;

        $columns = [];

        $gradeitemsalias = $this->get_table_alias('grade_items');

        $columns[] = (new column(
            'visible',
            new lang_string('gradetype', 'grades'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.gradetype")
            ->add_callback(static function(int $groupmode): string {
                $types = [
                    GRADE_TYPE_NONE => new lang_string('typenone', 'grades'),
                    GRADE_TYPE_VALUE => new lang_string('typevalue', 'grades'),
                    GRADE_TYPE_SCALE => new lang_string('typescale', 'grades'),
                    GRADE_TYPE_TEXT => new lang_string('typetext', 'grades'),
                ];

                return (string) ($types[$gradetype] ?? $gradetype);
            });

            return $columns;
    }

    /**
     * These are all the filters available to use in any report that uses this entity.
     *
     * @return filter[]
     *
     */
    protected function get_all_filters(): array {

        global $DB;

        $filters = [];

        $gradeitemsalias = $this->get_table_alias('grade_items');

        $filters[] = (new select(
            'gradetype',
            new lang_string('gradetype', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.gradetype")
            ->add_options([
                GRADE_TYPE_NONE => new lang_string('typenone', 'grades'),
                GRADE_TYPE_VALUE => new lang_string('typevalue', 'grades'),
                GRADE_TYPE_SCALE => new lang_string('typescale', 'grades'),
                GRADE_TYPE_TEXT => new lang_string('typetext', 'grades'),
            ]);

        return $filters;
    }

    /**
     * Return list of all available conditions - not used - same as filters
     *
     * @return array
     *
     */
    protected function get_all_conditions(): array {

        $conditions = [];

        return $conditions;
    }

}
