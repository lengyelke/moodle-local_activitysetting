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


use html_writer;
use lang_string;
use core_reportbuilder\local\filters\{boolean_select, date, duration, text, select, number};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;

use function DI\value;

/**
 * Class course_section
 *
 * This class represents a course section entity in the report builder.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_section extends base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'course_sections',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('coursesectionsetting', 'local_activitysetting');
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
     * Convert the section availability json to a human readable format based on the section ID.
     * This function is useful in a report column callback.
     * The result is either HTML or plain text based on the download parameter.
     *
     * @param int $sectionid
     * @return string
     */
    private static function format_section_availability($sectionid) {
        global $DB, $OUTPUT;

        // Get the basic course section record.
        $section = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
        $json = $section->availability;

        if (empty($json)) {
            return get_string('none', 'core');
        }

        // Get section_info and course context.
        $modinfo = get_fast_modinfo($section->course);
        $sectioninfo = $modinfo->get_section_info_by_id($sectionid);

        try {
            $info = new \core_availability\info_section($sectioninfo);
            $converted = $info->get_full_information();
            if (!is_string($converted)) {
                $renderable = new \core_availability\output\availability_info($converted);
                $notfullhtml = $OUTPUT->render($renderable);
            } else {
                $notfullhtml = $converted;
            }
            $html = \core_availability\info::format_info($notfullhtml, $section->course);

            // Remove HTML tags and decode HTML entities.
            $nonhtml = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Optional: Trim extra whitespace and normalize line breaks.
            $nonhtml = trim(preg_replace('/\s+/', ' ', $nonhtml));

            // Return HTML or plain text based on the download parameter.
            if (!isset($_GET['download'])) {
                return $html;
            } else {
                if ($_GET['download'] == 'html' || $_GET['download'] == 'pdf') {
                    return $html;
                } else {
                    return $nonhtml;
                }
            }
        } catch (Exception $e) {
            return get_string('error');
        }
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

        $columns = [];

        $sectionalias = $this->get_table_alias('course_sections');

        // Course section name column.
        $columns[] = (new column(
            'sectionname',
            new lang_string('sectionname', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$sectionalias}.name")
            ->add_field("{$sectionalias}.course")
            ->add_field("{$sectionalias}.section")
            ->add_callback(static function ($value, $row) {
                return $row->name ?: get_section_name($row->course, $row->section);
            });

        // Course section section number column.
        $columns[] = (new column(
            'sectionnumber',
            new lang_string('sectionnumber', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$sectionalias}.section");

        // Course section visible column.
        $columns[] = (new column(
            'visible',
            new lang_string('visible', 'core'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$sectionalias}.visible")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course section availability.
        $columns[] = (new column(
            'availability',
            new lang_string('accessrestrictions', 'availability'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$sectionalias}.id")
            ->add_callback(function ($value) {
                return self::format_section_availability($value);
            });

        // Course section component column, name of the delegated plugin.
        $columns[] = (new column(
            'component',
            new lang_string('component', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$sectionalias}.component")
            ->add_callback(function ($value) {
                return ($value == null) ? $value : get_string('pluginname', $value);
            });

        // Course section timemodified (last updated) column.
        $columns[] = (new column(
            'timemodified',
            new lang_string('timemodified', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$sectionalias}.timemodified")
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Returns list of all available filters
     *
     * These are all the filters available to use in any report that uses this entity.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $filters = [];

        $sectionalias = $this->get_table_alias('course_sections');

        // Course section section number filter.
        $filters[] = (new filter(
            number::class,
            'sectionnumber',
            new lang_string('sectionnumber', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$sectionalias}.section"
        ))
            ->add_joins($this->get_joins());

        // Course section visible filter.
        $filters[] = (new filter(
            boolean_select::class,
            'visible',
            new lang_string('sectionvisibility', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$sectionalias}.visible"
        ))
            ->add_joins($this->get_joins());

        // Course section component filter.
        $filters[] = (new filter(
            select::class,
            'component',
            new lang_string('component', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$sectionalias}.component"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(function () {
                global $CFG, $DB;
                $plugins = [];
                $components = $DB->get_fieldset_select('course_sections', 'DISTINCT component', '', [], 'component');
                foreach ($components as $component) {
                    if (!empty($component)) {
                        $plugins[$component] = get_string('pluginname', $component);
                    }
                }
                return $plugins;
            });

        // Course section timemodified (last updated) filter.
        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('timemodified', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$sectionalias}.timemodified"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

    /**
     * Returns list of all available conditions
     *
     * These are all the conditions available to use in any report that uses this entity.
     *
     * @return filter[]
     */
    protected function get_all_conditions(): array {
        $conditions = [];

        return $conditions;
    }
}
