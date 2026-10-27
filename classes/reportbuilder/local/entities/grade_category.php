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
use core_reportbuilder\local\filters\{number, text, select, date, boolean_select};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use local_activitysetting\reportbuilder\local\filters\gradecategorypath;
use local_activitysetting\reportbuilder\local\filters\topcategoryname;

/**
 * Class grade_category
 *
 * This class represents a grade category entity in the report builder.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_category extends base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'grade_categories',
            'grade_item',
            'scale',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('gradecategorysetting', 'local_activitysetting');
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

        $conditions = $this->get_all_conditions();
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

        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/lib.php');

        $columns = [];

        $gradecategoriesalias = $this->get_table_alias('grade_categories');
        $gradecategoryasitemalias = $this->get_table_alias('grade_item');
        $gradecategoryscalealias = $this->get_table_alias('scale');

        $this->add_join("LEFT JOIN {grade_items} {$gradecategoryasitemalias}
         ON {$gradecategoryasitemalias}.iteminstance = {$gradecategoriesalias}.id
         AND {$gradecategoryasitemalias}.itemtype in ('category', 'course')");

        $this->add_join("LEFT JOIN {scale} {$gradecategoryscalealias}
         ON {$gradecategoryscalealias}.id = {$gradecategoryasitemalias}.scaleid");

        // Grade category path.
        $columns[] = (new column(
            'categorypath',
            new lang_string('categorypath', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.path", 'categorypath_val')
            ->add_callback(static function ($value, $row): string {
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
            ->add_field("{$gradecategoriesalias}.depth", 'categorydepth_val')
            ->add_callback(static function ($value, $row): string {
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
            ->add_field("{$gradecategoriesalias}.path", 'categorypath_val')
            ->add_callback(static function ($value, $row): string {
                return self::get_top_category_name(
                    $row->categorypath_val ?? ''
                );
            });

        // Category full name.
        $columns[] = (new column(
            'fullname',
            new lang_string('fullname', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.fullname")
            ->add_field("{$gradecategoriesalias}.parent", 'parent_val')
            ->add_callback(static function ($value, $row): string {
                if (empty($row->parent_val) && $value === '?') {
                    return get_string('gradebookroot', 'local_activitysetting');
                }
                return (string)($value ?? '');
            });

        // Aggregation strategy.
        $columns[] = (new column(
            'aggregation',
            new lang_string('aggregation', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.aggregation")
            ->add_field("{$gradecategoryasitemalias}.calculation", 'calculation_val')
            ->add_callback(static function (int|null $value, $row): string {
                if ($value === null) {
                    return '';
                }

                // Get the standard aggregation string (e.g., "Weighted mean of grades").
                $strings = \grade_helper::get_aggregation_strings();
                $aggregationname = $strings[$value] ?? get_string('unknown', 'local_activitysetting');

                // If it's a calculated grade, add the prefix.
                if ($row->calculation_val !== null) {
                    return get_string('calculatedgrade', 'grades') . ' (' . $aggregationname . ')';
                }

                return $aggregationname;
            });

        // Keep the highest column.
        $columns[] = (new column(
            'keephigh',
            new lang_string('keephigh', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.keephigh");

        // Drop the lowest column.
        $columns[] = (new column(
            'droplow',
            new lang_string('droplow', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.droplow");

        // Exclude empty grades - Aggregated only flag.
        $columns[] = (new column(
            'aggregateonlygraded',
            new lang_string('aggregateonlygraded', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.aggregateonlygraded")
            ->add_callback([format::class, 'boolean_as_text']);

        // Include outcomes in aggregation - Aggregate outcomes flag.
        $columns[] = (new column(
            'aggregateoutcomes',
            new lang_string('aggregateoutcomes', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.aggregateoutcomes")
            ->add_callback([format::class, 'boolean_as_text']);

        // Category Hidden column with formatted display.
        $columns[] = (new column(
            'categoryhidden',
            new lang_string('categoryhidden', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.hidden")
            ->add_callback(function ($value) {
                return ($value === null) ? '' : self::format_hidden_value($value);
            });

        // Category total name (from grade_item table).
        $columns[] = (new column(
            'itemname',
            new lang_string('categorytotalname', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.itemname");

        // Item info for the category (from grade_item table).
        $columns[] = (new column(
            'iteminfo',
            new lang_string('iteminfo', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.iteminfo");

        // ID number for the category (from grade_item table).
        $columns[] = (new column(
            'idnumber',
            new lang_string('idnumbermod'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.idnumber");

        // Calculation for the category (from grade_item table).
        $columns[] = (new column(
            'calculation',
            new lang_string('calculation', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$gradecategoryasitemalias}.calculation")
            ->add_field("{$gradecategoriesalias}.courseid")
            ->add_callback(static function (?string $calculation, $row): string {
                if ($calculation === null) {
                    return '';
                }

                return \grade_item::denormalize_formula($calculation, $row->courseid);
            });

        // Grade type for the category (from grade_item table).
        $columns[] = (new column(
            'gradetype',
            new lang_string('gradetype', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.gradetype")
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

        // Grade max for the category (from grade_item table).
        $columns[] = (new column(
            'grademax',
            new lang_string('grademax', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.grademax")
            ->add_field("{$gradecategoryasitemalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) {
                // Check if gradetype is GRADE_TYPE_NONE and set grademax to an empty string.
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : $value;
            });

        // Grade min for the category (from grade_item table).
        $columns[] = (new column(
            'grademin',
            new lang_string('grademin', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.grademin")
            ->add_field("{$gradecategoryasitemalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) {
                // Check if gradetype is GRADE_TYPE_NONE and set grademin to an empty string.
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : $value;
            });

        // Scale name for the category (from scale table).
        $columns[] = (new column(
            'scalename',
            new lang_string('scale', 'core'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryscalealias}.name")
            ->add_callback(static function ($value): string {
                return (string) ($value ?? '');
            });

        // Grade pass for the category (from grade_item table).
        $columns[] = (new column(
            'gradepass',
            new lang_string('gradepass', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.gradepass")
            ->add_field("{$gradecategoryasitemalias}.gradetype", 'gradetype_val')
            ->add_callback(function ($value, $row) {
                // Check if gradetype is GRADE_TYPE_NONE and set gradepass to an empty string.
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : $value;
            });

        // Multiplicator column.
        $columns[] = (new column(
            'multfactor',
            new lang_string('multfactor', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.multfactor");

        // Offset column.
        $columns[] = (new column(
            'plusfactor',
            new lang_string('plusfactor', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.plusfactor");

        // Extra credit for the category (from grade_item table).
        $columns[] = (new column(
            'aggregationcoef',
            new lang_string('aggregationcoefextrasum', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.aggregationcoef");

        // Weight for the category (from grade_item table).
        $columns[] = (new column(
            'aggregationcoef2',
            new lang_string('weight', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.aggregationcoef2");

        // Sort order for the category (from grade_item table).
        $columns[] = (new column(
            'sortorder',
            new lang_string('sort_sortorder', 'admin'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.sortorder");

        // Grade display type for the category (from grade_item table).
        $columns[] = (new column(
            'display',
            new lang_string('gradedisplaytype', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.display")
            ->add_field("{$gradecategoriesalias}.courseid")
            ->add_callback(static function (int|null $display, $row): string {
                global $CFG;

                $options = [
                    GRADE_DISPLAY_TYPE_DEFAULT => get_string('default', 'grades'),
                    GRADE_DISPLAY_TYPE_REAL => get_string('real', 'grades'),
                    GRADE_DISPLAY_TYPE_PERCENTAGE => get_string('percentage', 'grades'),
                    GRADE_DISPLAY_TYPE_LETTER => get_string('letter', 'grades'),
                    GRADE_DISPLAY_TYPE_REAL_PERCENTAGE => get_string('realpercentage', 'grades'),
                    GRADE_DISPLAY_TYPE_REAL_LETTER => get_string('realletter', 'grades'),
                    GRADE_DISPLAY_TYPE_LETTER_REAL => get_string('letterreal', 'grades'),
                    GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE => get_string('letterpercentage', 'grades'),
                    GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER => get_string('percentageletter', 'grades'),
                    GRADE_DISPLAY_TYPE_PERCENTAGE_REAL => get_string('percentagereal', 'grades'),
                ];

                if ($display === GRADE_DISPLAY_TYPE_DEFAULT) {
                    $defaultdisplaytype = grade_get_setting(
                        $row->courseid,
                        'displaytype',
                        $CFG->grade_displaytype
                    );

                    if (isset($options[$defaultdisplaytype])) {
                        return get_string('defaultprev', 'grades', $options[$defaultdisplaytype]);
                    }
                }

                return $options[$display] ?? '';
            });

        // Grade display decimals for the category (from grade_item table).
        $columns[] = (new column(
            'decimals',
            new lang_string('decimalpoints', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.decimals")
            ->add_field("{$gradecategoriesalias}.courseid")
            ->add_callback(static function (int|null $decimals, $row): string {
                global $CFG;

                if ($decimals === null) {
                    $defaultgradedecimals = grade_get_setting(
                        $row->courseid,
                        'decimalpoints',
                        $CFG->grade_decimalpoints
                    );
                    return get_string('defaultprev', 'grades', $defaultgradedecimals);
                }
                return (string) $decimals;
            });

        // Category total hidden column with formatted display.
        $columns[] = (new column(
            'categorytotalhidden',
            new lang_string('categorytotalhidden', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.hidden")
            ->add_callback(function ($value) {
                return ($value === null) ? '' : self::format_hidden_value($value);
            });

        // Category total locked since column with formatted display.
        $columns[] = (new column(
            'categorytotallockedsince',
            new lang_string('categorytotallockedsince', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.locked")
            ->add_callback(function ($value) {
                return ($value === null) ? '' : self::format_locked_value($value, true);
            });

        // Category total locked after column with formatted display.
        $columns[] = (new column(
            'categorytotallockedafter',
            new lang_string('categorytotallockedafter', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.locktime")
            ->add_callback(function ($value) {
                return ($value === null) ? '' : self::format_locked_value($value, false);
            });

        // Weight adjusted for the category (from grade_item table).
        $columns[] = (new column(
            'weightoverride',
            new lang_string('adjustedweight', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoryasitemalias}.weightoverride")
            ->add_callback([format::class, 'boolean_as_text']);

        // Grade category time modified (last updated).
        $columns[] = (new column(
            'timemodified',
            new lang_string('timemodified', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$gradecategoriesalias}.timemodified")
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Format hidden value for display
     *
     * @param int $hidden
     * @return string
     */
    private static function format_hidden_value(int $hidden): string {
        global $CFG;

        if ($hidden === 0) {
            // Visible.
            return get_string('visible', 'core');
        } else {
            if ($hidden === 1) {
                // Hidden indefinitely.
                return get_string('hidden', 'grades');
            } else {
                // Hidden until date.
                $date = userdate($hidden, get_string('strftimedatetimeshort', 'core_langconfig'));
                return get_string('hiddenuntildate', 'local_activitysetting', $date);
            }
        }
    }

    /**
     * Format locked value for display
     *
     * @param int $locked
     * @param bool $since
     * @return string
     */
    private static function format_locked_value(int $locked, bool $since = false): string {
        global $CFG;

        if ($locked === 0) {
            // Not locked.
            return get_string('unlocked', 'local_activitysetting');
        } else {
            if ($locked === 1) {
                // Locked indefinitely.
                return get_string('locked', 'grades');
            } else {
                // Format locked date.
                $date = userdate($locked, get_string('strftimedatetimeshort', 'core_langconfig'));
                if ($since) {
                    return get_string('locktimedatesince', 'local_activitysetting', $date);
                } else {
                    return get_string('locktimedate', 'local_activitysetting', $date);
                }
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

        $filters = [];

        $gradecategoriesalias = $this->get_table_alias('grade_categories');
        $gradecategoryasitemalias = $this->get_table_alias('grade_item');
        $gradecategoryscalealias = $this->get_table_alias('scale');

        // Grade category path.
        $filters[] = (new filter(
            gradecategorypath::class,
            'categorypath',
            new lang_string('categorypath', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.path"
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
            "{$gradecategoriesalias}.depth"
        ))
            ->add_joins($this->get_joins());

        // Top level category name filter.
        $filters[] = (new filter(
            topcategoryname::class,
            'topcategoryname',
            new lang_string('topcategoryname', 'local_activitysetting'),
            $this->get_entity_name(),
            "CASE
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

        // Grade category full name filter.
        $filters[] = (new filter(
            text::class,
            'fullname',
            new lang_string('fullname', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.fullname"
        ))
            ->add_joins($this->get_joins());

        // Grade category aggregation filter.
        $filters[] = (new filter(
            select::class,
            'aggregation',
            new lang_string('aggregation', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.aggregation"
        ))
            ->add_joins($this->get_joins())
            ->set_options(\grade_helper::get_aggregation_strings());

        // Grade category Keep the highest filter.
        $filters[] = (new filter(
            number::class,
            'keephigh',
            new lang_string('keephigh', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.keephigh"
        ))
            ->add_joins($this->get_joins());

        // Grade category Drop the lowest filter.
        $filters[] = (new filter(
            number::class,
            'droplow',
            new lang_string('droplow', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.droplow"
        ))
            ->add_joins($this->get_joins());

        // Exclude empty grades filter.
        $filters[] = (new filter(
            boolean_select::class,
            'aggregateonlygraded',
            new lang_string('aggregateonlygraded', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.aggregateonlygraded"
        ))
            ->add_joins($this->get_joins());

        // Include outcomes in aggregation filter.
        $filters[] = (new filter(
            boolean_select::class,
            'aggregateoutcomes',
            new lang_string('aggregateoutcomes', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.aggregateoutcomes"
        ))
            ->add_joins($this->get_joins());

        // Category total name filter.
        $filters[] = (new filter(
            text::class,
            'itemname',
            new lang_string('categorytotalname', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.itemname"
        ))
            ->add_joins($this->get_joins());

        // Grade category item info filter.
        $filters[] = (new filter(
            text::class,
            'iteminfo',
            new lang_string('iteminfo', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.iteminfo"
        ))
            ->add_joins($this->get_joins());

        // Grade category ID number filter.
        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('idnumbermod'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.idnumber"
        ))
            ->add_joins($this->get_joins());

        // Grade category calculation filter.
        $filters[] = (new filter(
            text::class,
            'calculation',
            new lang_string('calculation', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.calculation"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                text::ANY_VALUE,
                text::IS_EMPTY,
                text::IS_NOT_EMPTY,
            ]);

        // Grade type filter.
        $filters[] = (new filter(
            select::class,
            'gradetype',
            new lang_string('gradetype', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.gradetype"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                GRADE_TYPE_NONE => new lang_string('modgradetypenone', 'grades'),
                GRADE_TYPE_VALUE => new lang_string('modgradetypepoint', 'grades'),
                GRADE_TYPE_SCALE => new lang_string('modgradetypescale', 'grades'),
                GRADE_TYPE_TEXT => new lang_string('typetext', 'grades'),
            ]);

        // Grade max filter.
        $filters[] = (new filter(
            number::class,
            'grademax',
            new lang_string('grademax', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.grademax"
        ))
            ->add_joins($this->get_joins());

        // Grade min filter.
        $filters[] = (new filter(
            number::class,
            'grademin',
            new lang_string('grademin', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.grademin"
        ))
            ->add_joins($this->get_joins());

        // Scale name filter.
        $filters[] = (new filter(
            text::class,
            'scalename',
            new lang_string('scale', 'core'),
            $this->get_entity_name(),
            "{$gradecategoryscalealias}.name"
        ))
            ->add_joins($this->get_joins());

        // Grade pass filter.
        $filters[] = (new filter(
            number::class,
            'gradepass',
            new lang_string('gradepass', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.gradepass"
        ))
            ->add_joins($this->get_joins());

        // Multiplicator filter.
        $filters[] = (new filter(
            number::class,
            'multfactor',
            new lang_string('multfactor', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.multfactor"
        ))
            ->add_joins($this->get_joins());

        // Offset filter.
        $filters[] = (new filter(
            number::class,
            'plusfactor',
            new lang_string('plusfactor', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.plusfactor"
        ))
            ->add_joins($this->get_joins());

        // Extra credit filter.
        $filters[] = (new filter(
            number::class,
            'aggregationcoef',
            new lang_string('aggregationcoefextrasum', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.aggregationcoef"
        ))
            ->add_joins($this->get_joins());

        // Weight filter.
        $filters[] = (new filter(
            number::class,
            'aggregationcoef2',
            new lang_string('weight', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.aggregationcoef2"
        ))
            ->add_joins($this->get_joins());

        // Sort order filter.
        $filters[] = (new filter(
            number::class,
            'sortorder',
            new lang_string('sort_sortorder', 'admin'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.sortorder"
        ))
            ->add_joins($this->get_joins());

        // Grade display type filter.
        $filters[] = (new filter(
            select::class,
            'display',
            new lang_string('gradedisplaytype', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.display"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                GRADE_DISPLAY_TYPE_DEFAULT => get_string('default', 'grades'),
                GRADE_DISPLAY_TYPE_REAL => get_string('real', 'grades'),
                GRADE_DISPLAY_TYPE_PERCENTAGE => get_string('percentage', 'grades'),
                GRADE_DISPLAY_TYPE_LETTER => get_string('letter', 'grades'),
                GRADE_DISPLAY_TYPE_REAL_PERCENTAGE => get_string('realpercentage', 'grades'),
                GRADE_DISPLAY_TYPE_REAL_LETTER => get_string('realletter', 'grades'),
                GRADE_DISPLAY_TYPE_LETTER_REAL => get_string('letterreal', 'grades'),
                GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE => get_string('letterpercentage', 'grades'),
                GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER => get_string('percentageletter', 'grades'),
                GRADE_DISPLAY_TYPE_PERCENTAGE_REAL => get_string('percentagereal', 'grades'),
            ]);

        // Grade display decimals filter.
        $filters[] = (new filter(
            select::class,
            'decimals',
            new lang_string('decimalpoints', 'grades'),
            $this->get_entity_name(),
            "CASE
                WHEN {$gradecategoryasitemalias}.decimals IS NULL THEN -1
                ELSE {$gradecategoryasitemalias}.decimals
                END"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                -1 => get_string('default', 'grades'),
                0 => '0',
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5',
            ]);

        // Weight adjusted filter.
        $filters[] = (new filter(
            boolean_select::class,
            'weightoverride',
            new lang_string('adjustedweight', 'grades'),
            $this->get_entity_name(),
            "{$gradecategoryasitemalias}.weightoverride"
        ))
            ->add_joins($this->get_joins());

        // Grade category time modified (last updated) filter.
        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('timemodified', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$gradecategoriesalias}.timemodified"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

    /**
     * Returns list of all available filters as conditions.
     * @return filter[]
     *
     */
    protected function get_all_conditions(): array {
        // Instead of duplicating the code, just return the filters.
        return $this->get_all_filters();
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

            $name = trim((string) $categories[$id]->fullname);

            if (empty($categories[$id]->parent) && $name === '?') {
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
     *
     * @param string|null $path
     * @return int
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

        return (int) $ids[1];
    }
}
