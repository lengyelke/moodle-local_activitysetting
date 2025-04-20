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
use core\output\html_writer;
use core_reportbuilder\local\filters\{boolean_select, date, number, select, text};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;

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
            'groupings',
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
     * Convert the availability json to a human readable format based on the course module ID.
     * This function is used in the format_availability column callback.
     * The result is either HTML or plain text based on the download parameter.
     *
     * @param int $id
     * @return string
     */
    private static function format_availability($id) {
        global $OUTPUT;

        // First get the basic course module record.
        $cm = get_coursemodule_from_instance('assign', $id, 0, false, MUST_EXIST);
        $json = $cm->availability;

        if (empty($json)) {
            return get_string('none', 'core');
        }

        // Then get the cm_info object.
        $modinfo = get_fast_modinfo($cm->course);
        $cminfo = $modinfo->get_cm($cm->id);

        try {
            $info = new \core_availability\info_module($cminfo);
            $tree = new \core_availability\tree(json_decode($json));
            $converted = $tree->get_full_information($info);
            if (!is_string($converted)) {
                $renderable = new \core_availability\output\availability_info($converted);
                $html = $OUTPUT->render($renderable);
            } else {
                $html = $converted;
            }

            // Remove HTML tags and decode HTML entities.
            $nonhtml = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Optional: Trim extra whitespace and normalize line breaks.
            $nonhtml = trim(preg_replace('/\s+/', ' ', $nonhtml));

            // Return the HTML or non-HTML version based on the download parameter.
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
     * @return columns[]
     */
    protected function get_all_columns(): array {

        global $CFG;

        $columns = [];

        $modulealias = $this->get_table_alias('course_modules');

        $modalias = $this->get_table_alias('modules');
        $this->add_join("JOIN {modules} {$modalias}
                        ON {$modalias}.id = {$modulealias}.module");

        $groupingalias = $this->get_table_alias('groupings');

        $this->add_join("LEFT JOIN {groupings} {$groupingalias}
                        ON {$groupingalias}.id = {$modulealias}.groupingid
                        AND {$groupingalias}.courseid = {$modulealias}.course");

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

        // Course module grouping name.
        $columns[] = (new column(
            'groupingname',
            new lang_string('groupingname', 'group'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$groupingalias}.name")
            ->add_callback(static function($value): string {
                return $value ?: get_string('none');
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

        // Course module completionexpected.
        $columns[] = (new column(
            'completionexpected',
            new lang_string('completionexpected', 'completion'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.completionexpected")
            ->add_callback([format::class, 'userdate']);

        // Course module completionpassgrade.
        $columns[] = (new column(
            'completionpassgrade',
            new lang_string('completionpassgrade', 'completion'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.completionpassgrade")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course module showdescription.
        $columns[] = (new column(
            'showdescription',
            new lang_string('showdescription', 'core'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.showdescription")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course module availability.
        $columns[] = (new column(
            'availability',
            new lang_string('accessrestrictions', 'availability'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.instance")
            ->add_callback(function($value) {
                return self::format_availability($value);
            });

        // Course module deletioninprogress.
        $columns[] = (new column(
            'deletioninprogress',
            new lang_string('deletioninprogress', 'local_activitysetting'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.deletioninprogress")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course module downloadcontent.
        $columns[] = (new column(
            'downloadcontent',
            new lang_string('downloadcontent', 'course'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.downloadcontent")
            ->add_callback([format::class, 'boolean_as_text']);

        // Course module lang.
        $columns[] = (new column(
            'lang',
            new lang_string('forcelanguage', 'core'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$modulealias}.lang");

        // Course module URL.
        $columns[] = (new column(
            'cmURL',
            new lang_string('urlastext', 'local_activitysetting'),
            $this->get_entity_name()
            ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$modalias}.name", "modname") // Add the module name field.
            ->add_field("{$modulealias}.id", "cmid") // Add the course module ID field.
            ->add_callback(function($value, $row) use ($CFG) {
                // Extract the fields.
                $modulename = $row->modname;
                $cmid = $row->cmid;

                // Construct the URL.
                if ($modulename && $cmid) {
                    return html_writer::link(
                        $CFG->wwwroot . '/mod/' . $modulename . '/view.php?id=' . $cmid,
                        $CFG->wwwroot . '/mod/' . $modulename . '/view.php?id=' . $cmid,
                        ['target' => '_blank']
                    );
                }

                // Return a default value if fields are missing.
                return get_string('none');
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

        $modulealias = $this->get_table_alias('course_modules');
        $groupingalias = $this->get_table_alias('groupings');

        $this->add_join("LEFT JOIN {groupings} {$groupingalias}
                        ON {$groupingalias}.id = {$modulealias}.groupingid
                        AND {$groupingalias}.courseid = {$modulealias}.course");

        // General filters
        // Course module visible.
        $filters[] = (new filter(
            boolean_select::class,
            'visible',
            new lang_string('visible', 'core'),
            $this->get_entity_name(),
            "{$modulealias}.visible"
        ))
            ->add_joins($this->get_joins());

        // Course module hideoncoursepage.
        $filters[] = (new filter(
            boolean_select::class,
            'visibleoncoursepage',
            new lang_string('hideoncoursepage', 'core'),
            $this->get_entity_name(),
            "{$modulealias}.visibleoncoursepage"
        ))
            ->add_joins($this->get_joins());

        // Course module visibleold.
        $filters[] = (new filter(
            boolean_select::class,
            'visibleold',
            new lang_string('visibleold', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$modulealias}.visibleold"
        ))
            ->add_joins($this->get_joins());

        // Course module ID number.
        $filters[] = (new filter(
            number::class,
            'idnumber',
            new lang_string('idnumber', 'core'),
            $this->get_entity_name(),
            "{$modulealias}.idnumber"
        ))
            ->add_joins($this->get_joins());

        // Course module groupmode.
        $filters[] = (new filter(
            select::class,
            'groupmode',
            new lang_string('groupmode', 'core'),
            $this->get_entity_name(),
            "{$modulealias}.groupmode"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                NOGROUPS => new lang_string('groupsnone', 'core'),
                SEPARATEGROUPS => new lang_string('groupsseparate', 'core'),
                VISIBLEGROUPS => new lang_string('groupsvisible', 'core'),
            ]);

        // Course module grouping name.
        $filters[] = (new filter(
            text::class,
            'groupingname',
            new lang_string('groupingname', 'group'),
            $this->get_entity_name(),
            "{$groupingalias}.name"
        ))
            ->add_joins($this->get_joins());

        // Course module completion.
        $filters[] = (new filter(
            select::class,
            'completion',
            new lang_string('completion', 'completion'),
            $this->get_entity_name(),
            "{$modulealias}.completion"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                COMPLETION_TRACKING_NONE => new lang_string('completion_none', 'completion'),
                COMPLETION_TRACKING_MANUAL => new lang_string('completion_manual', 'completion'),
                COMPLETION_TRACKING_AUTOMATIC => new lang_string('completion_automatic', 'completion'),
            ]);

        // Course module completionview.
        $filters[] = (new filter(
            boolean_select::class,
            'completionview',
            new lang_string('completionview_desc', 'completion'),
            $this->get_entity_name(),
            "{$modulealias}.completionview"
        ))
            ->add_joins($this->get_joins());

        // Course module completionexpected.
        $filters[] = (new filter(
            date::class,
            'completionexpected',
            new lang_string('completionexpected', 'completion'),
            $this->get_entity_name(),
            "{$modulealias}.completionexpected"
        ))
            ->add_joins($this->get_joins());

        // Course module completionpassgrade.
        $filters[] = (new filter(
            boolean_select::class,
            'completionpassgrade',
            new lang_string('completionpassgrade', 'completion'),
            $this->get_entity_name(),
            "{$modulealias}.completionpassgrade"
        ))
            ->add_joins($this->get_joins());

        // Course module showdescription.
        $filters[] = (new filter(
            boolean_select::class,
            'showdescription',
            new lang_string('showdescription', 'core'),
            $this->get_entity_name(),
            "{$modulealias}.showdescription"
        ))
            ->add_joins($this->get_joins());

        // Course module deletioninprogress.
        $filters[] = (new filter(
            boolean_select::class,
            'deletioninprogress',
            new lang_string('deletioninprogress', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$modulealias}.deletioninprogress"
        ))
            ->add_joins($this->get_joins());

        // Course module downloadcontent.
        $filters[] = (new filter(
            boolean_select::class,
            'downloadcontent',
            new lang_string('downloadcontent', 'course'),
            $this->get_entity_name(),
            "{$modulealias}.downloadcontent"
        ))
            ->add_joins($this->get_joins());

        // Course module lang.
        $filters[] = (new filter(
            select::class,
            'lang',
            new lang_string('forcelanguage', 'core'),
            $this->get_entity_name(),
            "{$modulealias}.lang"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                get_string_manager()->get_list_of_translations(),
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
