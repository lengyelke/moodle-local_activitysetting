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

use lang_string;
use question_engine;
use core_reportbuilder\local\filters\{boolean_select, date, duration, text, select, number};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use mod_quiz\question\display_options;

/**
 * Class quiz
 *
 * This entity represents a quiz activity setting in the report.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scorm extends base {

    /**
     * Database tables that this entity uses
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'scorm',
            ];
    }

    /**
     * Default title for this entity
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('scormsetting', 'local_activitysetting');
    }

    /**
     * Initialise the entity.
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
     * @return column[]
     */
    protected function get_all_columns(): array {

        global $DB, $CFG;

        $scormalias = $this->get_table_alias('scorm');

        $columns = [];

        // Scorm name.
        $columns[] = (new column(
            'scormname',
            new lang_string('name', 'core'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.name");

        return $columns;
    }

    /**
     * All report filters.
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $scormalias = $this->get_table_alias('scorm');

        $filters = [];

        // Scorm name filter.
        $filters[] = (new filter(
            text::class,
            'scormname',
            new lang_string('name', 'core'),
            $this->get_entity_name(),
            "{$scormalias}.name"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
