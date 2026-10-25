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

        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $columns = [];

        $gradeitemsalias = $this->get_table_alias('grade_items');
        $scalealias = $this->get_table_alias('scale');

        $this->add_join("LEFT JOIN {scale} {$scalealias} ON {$scalealias}.id = {$gradeitemsalias}.scaleid");

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

        // Grade items iteminfo.
        $columns[] = (new column(
            'iteminfo',
            new lang_string('iteminfo', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.iteminfo");

        // Grade items idnumber.
        $columns[] = (new column(
            'idnumber',
            new lang_string('idnumbermod'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.idnumber");

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
            ->add_callback(function ($value, $row) {
                // Check if gradetype is GRADE_TYPE_NONE and set grademin to an empty string.
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
            ->add_callback(function ($value, $row) {
                // Check if gradetype is GRADE_TYPE_NONE and set grademax to an empty string.
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
            ->add_field("{$gradeitemsalias}.multfactor");

        // Offset column.
        $columns[] = (new column(
            'plusfactor',
            new lang_string('plusfactor', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.plusfactor");

        // Grade display type column.
        $columns[] = (new column(
            'display',
            new lang_string('gradedisplaytype', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.display")
            ->add_field("{$gradeitemsalias}.courseid")
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

        // Grade display decimal points column.
        $columns[] = (new column(
            'decimals',
            new lang_string('decimalpoints', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.decimals")
            ->add_field("{$gradeitemsalias}.courseid")
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

        // Scale name column.
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
            ->add_callback(function ($value, $row) {
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
            ->add_callback(function ($value, $row) {
                return ($row->gradetype_val == GRADE_TYPE_NONE) ? '' : self::format_locked_value($value);
            });

        // Weight column.
        $columns[] = (new column(
            'aggregationcoef2',
            new lang_string('weight', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.aggregationcoef2");

        // Weight adjusted column.
        $columns[] = (new column(
            'weightoverride',
            new lang_string('adjustedweight', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.weightoverride")
            ->add_callback([format::class, 'boolean_as_text']);

        // Extra credit column.
        $columns[] = (new column(
            'aggregationcoef',
            new lang_string('aggregationcoefextrasum', 'grades'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.aggregationcoef");

        // Sort order column.
        $columns[] = (new column(
            'sortorder',
            new lang_string('sort_sortorder', 'admin'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$gradeitemsalias}.sortorder");

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
     * @param int $locked
     * @return string
     */
    private static function format_locked_value(int $locked): string {
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
        $scalealias = $this->get_table_alias('scale');

        // Grade items itemname.
        $filters[] = (new filter(
            text::class,
            'itemname',
            new lang_string('itemname', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.itemname"
        ))
            ->add_joins($this->get_joins());

        // Grade items iteminfo filter.
        $filters[] = (new filter(
            text::class,
            'iteminfo',
            new lang_string('iteminfo', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.iteminfo"
        ))
            ->add_joins($this->get_joins());

        // Grade items idnumber filter.
        $filters[] = (new filter(
            text::class,
            'idnumber',
            new lang_string('idnumbermod'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.idnumber"
        ))
            ->add_joins($this->get_joins());

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

        // Multiplicator filter.
        $filters[] = (new filter(
            number::class,
            'multfactor',
            new lang_string('multfactor', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.multfactor"
        ))
            ->add_joins($this->get_joins());

        // Offset filter.
        $filters[] = (new filter(
            number::class,
            'plusfactor',
            new lang_string('plusfactor', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.plusfactor"
        ))
            ->add_joins($this->get_joins());

        // Grade display type filter.
        $filters[] = (new filter(
            select::class,
            'display',
            new lang_string('gradedisplaytype', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.display"
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

        // Grade display decimal points filter.
        $filters[] = (new filter(
            select::class,
            'decimals',
            new lang_string('decimalpoints', 'grades'),
            $this->get_entity_name(),
            "CASE
                WHEN {$gradeitemsalias}.decimals IS  NULL THEN -1
                ELSE {$gradeitemsalias}.decimals
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

        // Scale name filter.
        $filters[] = (new filter(
            text::class,
            'scalename',
            new lang_string('scale', 'core'),
            $this->get_entity_name(),
            "{$scalealias}.name"
        ))
            ->add_joins($this->get_joins());

        // Weight filter.
        $filters[] = (new filter(
            number::class,
            'aggregationcoef2',
            new lang_string('weight', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.aggregationcoef2"
        ))
            ->add_joins($this->get_joins());

        // Weight adjusted filter.
        $filters[] = (new filter(
            boolean_select::class,
            'weightoverride',
            new lang_string('adjustedweight', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.weightoverride"
        ))
            ->add_joins($this->get_joins());

        // Extra credit filter.
        $filters[] = (new filter(
            number::class,
            'aggregationcoef',
            new lang_string('aggregationcoefextrasum', 'grades'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.aggregationcoef"
        ))
            ->add_joins($this->get_joins());

            // Grade items sort order filter.
        $filters[] = (new filter(
            number::class,
            'sortorder',
            new lang_string('sort_sortorder', 'admin'),
            $this->get_entity_name(),
            "{$gradeitemsalias}.sortorder"
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
     * Returns list of all available filters as conditions.
     * @return filter[]
     *
     */
    protected function get_all_conditions(): array {
        // Instead of duplicating the code, just return the filters.
        return $this->get_all_filters();
    }
}
