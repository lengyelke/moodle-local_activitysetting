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
use core_reportbuilder\local\filters\{number, text, select, date};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use local_activitysetting\reportbuilder\local\filters\gradecategorypath;
use local_activitysetting\reportbuilder\local\filters\topcategoryname;

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
            'grade_items',
            'scale',
            'grade_categories',
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
     * @return column[]
     */
    protected function get_all_columns(): array {

        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $columns = [];

        $gradeitemsalias = $this->get_table_alias('grade_items');
        $scalealias = $this->get_table_alias('scale');
        $gradecategoriesalias = $this->get_table_alias('grade_categories');

        $this->add_join("LEFT JOIN {scale} {$scalealias} ON {$scalealias}.id = {$gradeitemsalias}.scaleid");
        $this->add_join("LEFT JOIN {grade_categories} {$gradecategoriesalias}
                            ON {$gradecategoriesalias}.id = {$gradeitemsalias}.categoryid");

        // Grade items itemname.
        $columns[] = (new column(
            'itemname',
            new lang_string('itemname', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.itemname");

        // Grade category path.
        $columns[] = (new column(
            'categorypath',
            new lang_string('categorypath', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_field("{$gradecategoriesalias}.path", 'categorypath_val')
            ->add_callback(static function ($value, $row): string {
                if ((int)$row->gradetype_val === GRADE_TYPE_NONE) {
                    return '';
                }
                return self::format_grade_category_path($row->categorypath_val ?? '');
            });

        // Grade category depth.
        $columns[] = (new column(
            'categorydepth',
            new lang_string('categorydepth', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_field("{$gradecategoriesalias}.depth", 'categorydepth_val')
            ->add_callback(static function ($value, $row): string {
                if ((int)$row->gradetype_val === GRADE_TYPE_NONE) {
                    return '';
                }
                return (string)($row->categorydepth_val ?? '');
            });

        // Top level category name.
        $columns[] = (new column(
            'topcategoryname',
            new lang_string('topcategoryname', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_field("{$gradecategoriesalias}.path", 'categorypath_val')
            ->add_callback(static function ($value, $row): string {
                // Ungraded items are treated as having no category path.
                if ((int)$row->gradetype_val === GRADE_TYPE_NONE) {
                    return '';
                }

                return self::get_top_category_name(
                    $row->categorypath_val ?? ''
                );
            });

        // Grade items grade type.
        $columns[] = (new column(
            'gradetype',
            new lang_string('gradetype', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.gradetype")
            ->add_callback(static function (int|float|null $gradetype): string {
                $types = [
                    GRADE_TYPE_NONE => new lang_string('modgradetypenone', 'grades'),
                    GRADE_TYPE_VALUE => new lang_string('modgradetypepoint', 'grades'),
                    GRADE_TYPE_SCALE => new lang_string('modgradetypescale', 'grades'),
                    GRADE_TYPE_TEXT => new lang_string('typetext', 'grades'),
                ];
                $key = (int) $gradetype;
                return (string) (
                    $types[$key]
                    ?? ($gradetype === null
                        ? get_string('notset', 'local_activitysetting')
                        : get_string('unknown', 'local_activitysetting')
                    )
                );
            });

        // Grade items grade min.
        $columns[] = (new column(
            'grademin',
            new lang_string('grademin', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.grademin")
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) use ($gradeitemsalias) {
                // Check if gradetype is GRADE_TYPE_NONE and set gradepass to an empty string.
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : $value;
            });

        // Grade items grade max.
        $columns[] = (new column(
            'grademax',
            new lang_string('grademax', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.grademax")
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) use ($gradeitemsalias) {
                // Check if gradetype is GRADE_TYPE_NONE and set gradepass to an empty string.
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : $value;
            });

        // Grade items grade pass.
        $columns[] = (new column(
            'gradepass',
            new lang_string('gradepass', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.gradepass")
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) use ($gradeitemsalias) {
                // Check if gradetype is GRADE_TYPE_NONE and set gradepass to an empty string.
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : $value;
            });

        // Hidden column with formatted display.
        $columns[] = (new column(
            'hidden',
            new lang_string('hidden', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.hidden")
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) use ($gradeitemsalias) {
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : self::format_hidden_value($value);
            });

        // Locked column with formatted display.
        $columns[] = (new column(
            'locked',
            new lang_string('locked', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.locked")
            ->add_field("{$gradeitemsalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) use ($gradeitemsalias) {
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : self::format_locked_value($value);
            });

        // Add the new 'scalename' column.
        $columns[] = (new column(
            'scalename',
            new lang_string('scale', 'core'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$scalealias}.name")
            ->add_callback(static function ($value): string {
                // Optional: Handle NULL cases gracefully if needed.
                return $value ?? ''; // If scaleid was NULL/0, name will be NULL. Return empty string.
            });

        // Grade items timemodified (last updated).
        $columns[] = (new column(
            'timemodified',
            new lang_string('timemodified', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.timemodified")
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Format hidden value for display
     *
     * @param string $hidden
     * @return string
     */
    private static function format_hidden_value(string $hidden): string {
        global $CFG;

        if ($hidden == 0) {
            // Visible.
            return get_string('visible', 'core');
        } else {
            if ($hidden == 1) {
                // Hidden indefinitely.
                return get_string('hidden', 'grades');
            } else {
                // Hidden until date.
                require_once($CFG->libdir . '/gradelib.php');
                $date = userdate($hidden, get_string('strftimedatetimeshort', 'core_langconfig'));
                return get_string('hiddenuntildate', 'local_activitysetting', $date);
            }
        }
    }

    /**
     * Format locked value for display
     *
     * @param string $locked
     * @return string
     */
    private static function format_locked_value(string $locked): string {
        global $CFG;

        if ($locked == 0) {
            // Not locked.
            return get_string('unlocked', 'local_activitysetting');
        } else {
            if ($locked == 1) {
                // Locked indefinitely.
                return get_string('locked', 'grades');
            } else {
                // Locked after date.
                require_once($CFG->libdir . '/gradelib.php');
                $date = userdate($locked, get_string('strftimedatetimeshort', 'core_langconfig'));
                return get_string('locktimedate', 'local_activitysetting', $date);
            }
        }
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
        $gradecategoriesalias = $this->get_table_alias('grade_categories');
        $this->add_join("LEFT JOIN {grade_categories} {$gradecategoriesalias}
                            ON {$gradecategoriesalias}.id = {$gradeitemsalias}.categoryid");

        // Grade items itemname.
        $filters[] = (new filter(
            text::class,
            'itemname',
            new lang_string('itemname', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.itemname"
        ))
            ->add_joins($this->get_joins());

        // Grade items category path.
        $filters[] = (new filter(
            gradecategorypath::class,
            'categorypath',
            new lang_string('categorypath', 'local_activitysetting'),
            $this->get_entity_name(),
            "CASE
                WHEN {$gradeitemsalias}.gradetype = " . GRADE_TYPE_NONE . "
                THEN NULL
                ELSE {$gradecategoriesalias}.path
            END"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                gradecategorypath::ANY_VALUE,
                gradecategorypath::CONTAINS,
                gradecategorypath::DOES_NOT_CONTAIN,
                gradecategorypath::IS_EMPTY,
                gradecategorypath::IS_NOT_EMPTY,
            ]);

        // Grade category depth filter.
        $filters[] = (new filter(
            number::class,
            'categorydepth',
            new lang_string('categorydepth', 'local_activitysetting'),
            $this->get_entity_name(),
            "CASE
                WHEN {$gradeitemsalias}.gradetype = " . GRADE_TYPE_NONE . "
                THEN NULL
                ELSE {$gradecategoriesalias}.depth
            END"
        ))
            ->add_joins($this->get_joins());

        // Top level category name filter.
        $filters[] = (new filter(
            topcategoryname::class,
            'topcategoryname',
            new lang_string('topcategoryname', 'local_activitysetting'),
            $this->get_entity_name(),
            "CASE
                WHEN {$gradeitemsalias}.gradetype = " . GRADE_TYPE_NONE . "
                THEN NULL
                WHEN {$gradecategoriesalias}.depth = 1
                THEN null
                ELSE {$gradecategoriesalias}.path
            END"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                topcategoryname::ANY_VALUE,
                topcategoryname::CONTAINS,
                topcategoryname::DOES_NOT_CONTAIN,
                topcategoryname::IS_EMPTY,
                topcategoryname::IS_NOT_EMPTY,
            ]);

        // Grade items grade type.
        $filters[] = (new filter(
            select::class,
            'gradetype',
            new lang_string('gradetype', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.gradetype"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                GRADE_TYPE_NONE => new lang_string('modgradetypenone', 'grades'),
                GRADE_TYPE_VALUE => new lang_string('modgradetypepoint', 'grades'),
                GRADE_TYPE_SCALE => new lang_string('modgradetypescale', 'grades'),
                GRADE_TYPE_TEXT => new lang_string('typetext', 'grades'),
            ]);

        // Grade items grade min.
        $filters[] = (new filter(
            number::class,
            'grademin',
            new lang_string('grademin', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.grademin"
        ))
            ->add_joins($this->get_joins());

        // Grade items grade max.
        $filters[] = (new filter(
            number::class,
            'grademax',
            new lang_string('grademax', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.grademax"
        ))
            ->add_joins($this->get_joins());

        // Grade items grade pass.
        $filters[] = (new filter(
            number::class,
            'gradepass',
            new lang_string('gradepass', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.gradepass"
        ))
            ->add_joins($this->get_joins());

        // Grade items timemodified (last updated) filter.
        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('timemodified', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.timemodified"
        ))
            ->add_joins($this->get_joins());

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
    /**
     * Format grade category path for display.
     *
     * Converts a stored path like /1/5/9 into "Category 1 / Category 2 / Category 3".
     *
     * @param string|null $path
     * @return string
     */
    private static function format_grade_category_path(?string $path): string {
        global $DB;

        static $cache = [];

        $cachekey = $path;
        if (empty($path)) {
            return '';
        }

        if (isset($cache[$cachekey])) {
            return $cache[$cachekey];
        }

        $ids = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
        if (!$ids) {
            return $cache[$cachekey] = '';
        }

        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $categories = $DB->get_records_select(
            'grade_categories',
            "id {$insql}",
            $params,
            '',
            'id, fullname, parent'
        );

        $parts = [];
        foreach ($ids as $id) {
            if (!isset($categories[$id])) {
                continue;
            }

            $name = trim((string)$categories[$id]->fullname);

            if (empty($categories[$id]->parent)) {
                $name = get_string('gradebookroot', 'local_activitysetting');
            }

            if ($name !== '') {
                $parts[] = format_string($name);
            }
        }

        return $cache[$cachekey] = implode(' / ', $parts);
    }

    /**
     * Return the top-level grade category name from a category path.
     *
     * For a path like:
     *   /1/2/5/9
     *
     * Returns the name of category ID 2.
     *
     * @param string|null $path
     * @return string
     */
    private static function get_top_category_name(?string $path): string {
        global $DB;

        static $cache = [];

        if (empty($path)) {
            return '';
        }

        $topcategoryid = self::get_top_category_id($path);

        if ($topcategoryid <= 0) {
            return '';
        }

        if (!array_key_exists($topcategoryid, $cache)) {
            $record = $DB->get_record(
                'grade_categories',
                ['id' => $topcategoryid],
                'fullname',
                IGNORE_MISSING
            );

            $cache[$topcategoryid] = $record
                ? format_string($record->fullname)
                : '';
        }

        return $cache[$topcategoryid];
    }

    /**
     * Get the top-level category ID from a category path.
     *
     * For a path like:
     *   /1/2/5/9
     *  Returns 2.
     */
    private static function get_top_category_id(?string $path): int {
        if (empty($path)) {
            return 0;
        }

        $ids = array_values(array_filter(
            explode('/', trim($path, '/')),
            'strlen'
        ));

        // Need at least root category + top-level category.
        if (!isset($ids[1])) {
            return 0;
        }

        return (int)$ids[1];
    }
}
