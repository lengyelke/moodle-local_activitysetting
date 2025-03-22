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
use core_reportbuilder\local\filters\{boolean_select, date, duration, number, text};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\helpers\custom_fields;

/**
 * Class course_module
 *
 * This class represents the course module entity.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module extends base {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'course_modules',
            'modules',
            'course',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('coursemodulesetting', 'local_activitysetting');
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

        $modulealias = $this->get_table_alias('course_modules');

        // General columns
        // Course module visible.
        $columns[] = (new column(
            'visible',
            new lang_string('visible', 'core'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.visible")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course module hideoncoursepage.
        $columns[] = (new column(
            'visibleoncoursepage',
            new lang_string('hideoncoursepage', 'core'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.visibleoncoursepage")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course module visibleold.
        $columns[] = (new column(
            'visibleold',
            new lang_string('visibleold', 'local_activitysetting'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.visibleold")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course module ID number.
        $columns[] = (new column(
            'idnumber',
            new lang_string('idnumber', 'core'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.idnumber");

        // Course module groupmode.
        $columns[] = (new column(
            'groupmode',
            new lang_string('groupmode', 'core'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.groupmode")
            ->add_callback(static function(int $groupmode): string {
                $modes = [
                    NOGROUPS => new lang_string('groupsnone', 'core'),
                    SEPARATEGROUPS => new lang_string('groupsseparate', 'core'),
                    VISIBLEGROUPS => new lang_string('groupsvisible', 'core'),
                ];

                return (string) ($modes[$groupmode] ?? $groupmode);
            });

        // Course module completion.
        $columns[] = (new column(
            'completion',
            new lang_string('completion', 'completion'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.completion")
            ->add_callback(static function(int $completion): string {
                $modes = [
                    COMPLETION_TRACKING_NONE => new lang_string('completion_none', 'completion'),
                    COMPLETION_TRACKING_MANUAL => new lang_string('completion_manual', 'completion'),
                    COMPLETION_TRACKING_AUTOMATIC => new lang_string('completion_automatic', 'completion'),
                ];

                return (string) ($modes[$completion] ?? $completion);
            });

        // Course module completionview.
        $columns[] = (new column(
            'completionview',
            new lang_string('completionview_desc', 'completion'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.completionview")
            ->add_callback([format::class, 'boolean_as_text']);

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

        $modulealias = $this->get_table_alias('course_modules');

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
