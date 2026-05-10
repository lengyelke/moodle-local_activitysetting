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

namespace local_activitysetting\reportbuilder\local\helpers;

use core_reportbuilder\local\report\column;
use html_writer;
use lang_string;
use moodle_url;
use stdClass;

/**
 * Class activity_link_column_helper
 *
 * @package    local_activitysetting
 * @copyright  2026 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_link_column_helper {
    /**
     * Creates a column definition for a report builder report that displays an activity name with a link to the activity.
     * The column expects the SQL to return both the activity name and the course module ID, which it uses to generate the link.
     * The column will be sortable by the activity name.
     * @param string $columnname The unique name for the column.
     * @param lang_string $title The display title for the column.
     * @param string $entityname The name of the entity this column belongs to.
     * @param string $namesql The SQL expression to retrieve the activity name.
     * @param string $cmidsql The SQL expression to retrieve the course module ID.
     * @param array $joins Optional array of join definitions to include in the report query.
     * @param array $sortfields Optional array of SQL expressions to use for sorting the column.
     * @param string $pathname Optional path to the activity view page, used to generate the link.
     * @return column The configured column definition for the report builder.
     */
    public static function create_name_with_link_column(
        string $columnname,
        lang_string $title,
        string $entityname,
        string $namesql,
        string $cmidsql,
        array $joins = [],
        array $sortfields = [],
        string $pathname = ''
    ): column {
        $namefield = $columnname . '_name';
        $cmidfield = $columnname . '_cmid';

        return (new column($columnname, $title, $entityname))
            ->add_joins($joins)
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true, $sortfields ?: [$namesql])
            ->add_field($namesql, $namefield)
            ->add_field($cmidsql, $cmidfield)
            ->add_callback([self::class, 'format_name_with_link'], [
                'namefield' => $namefield,
                'cmidfield' => $cmidfield,
                'pathname' => $pathname,
            ]);
    }

    /**
     * Formats the activity name as a link to the activity view page using the course module ID.
     * Expects the row to contain both the activity name and the course module ID, as defined by the column's callback arguments.
     * If the course module ID is missing or invalid, it will return just the formatted activity name without a link.
     * If an aggregation is being performed (e.g. for totals), it will return just the formatted activity name without a link.
     *
     * @param string|null $value The raw value of the activity name from the SQL query (not used in this implementation).
     * @param stdClass $row The full data row from the SQL query.
     * @param array $args The callback arguments defined in the column, containing 'namefield', 'cmidfield', and 'pathname'.
     * @param string|null $aggregation The type of aggregation being performed.
     * @return string The formatted activity name, optionally wrapped in a link to the activity view page.
     */
    public static function format_name_with_link(
        ?string $value,
        stdClass $row,
        array $args = [],
        ?string $aggregation = null
    ): string {
        $namefield = $args['namefield'] ?? '';
        $cmidfield = $args['cmidfield'] ?? '';
        $pathname = $args['pathname'] ?? '';

        $name = '';
        if ($namefield !== '' && isset($row->{$namefield})) {
            $name = format_string((string)$row->{$namefield});
        } else if ($value !== null) {
            $name = format_string($value);
        }

        if ($name === '') {
            return '';
        }

        if ($aggregation !== null) {
            return $name;
        }

        $cmid = null;
        if ($cmidfield !== '' && !empty($row->{$cmidfield})) {
            $cmid = (int)$row->{$cmidfield};
        }

        if (empty($cmid) || $pathname === '') {
            return $name;
        }

        return html_writer::link(
            new moodle_url($pathname, ['id' => $cmid]),
            $name
        );
    }
}
