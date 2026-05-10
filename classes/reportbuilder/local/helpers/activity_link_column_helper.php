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

defined('MOODLE_INTERNAL') || die();

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
