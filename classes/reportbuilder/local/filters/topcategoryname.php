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

namespace local_activitysetting\reportbuilder\local\filters;

use core_reportbuilder\local\filters\text;

/**
 * Class topcategoryname
 *
 * @package    local_activitysetting
 * @copyright  2026 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topcategoryname extends text {
    /**
     * Get SQL filter for grade category path.
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $operator = (int) ($values["{$this->name}_operator"] ?? self::ANY_VALUE);
        $fieldsql = $this->filter->get_field_sql();

        switch ($operator) {
            case self::ANY_VALUE:
                return ['', []];

            case self::IS_EMPTY:
                return [
                    "COALESCE($fieldsql, '') = ''",
                    [],
                ];

            case self::IS_NOT_EMPTY:
                return [
                    "COALESCE($fieldsql, '') <> ''",
                    [],
                ];
        }

        $value = trim($values["{$this->name}_value"] ?? '');

        if ($value === '') {
            return ['', []];
        }

        $params = [];

        switch ($operator) {
            case self::CONTAINS:
            case self::DOES_NOT_CONTAIN:
                $categoriesql = $DB->sql_like('gc.fullname', ':search', false);
                $params['search'] = '%' . $value . '%';
                break;
            // These operators make no sense for a path, but we will support it for future proofing.
            // The following operators are not enabled in the filter.
            case self::IS_EQUAL_TO:
            case self::IS_NOT_EQUAL_TO:
                $categoriesql = $DB->sql_equal('gc.fullname', ':search', false, false);
                $params['search'] = $value;
                break;

            case self::STARTS_WITH:
                $categoriesql = $DB->sql_like('gc.fullname', ':search', false);
                $params['search'] = $value . '%';
                break;

            case self::ENDS_WITH:
                $categoriesql = $DB->sql_like('gc.fullname', ':search', false);
                $params['search'] = '%' . $value;
                break;

            default:
                return ['', []];
        }

        $existssql = "
            EXISTS (
                SELECT 1
                    FROM {grade_categories} gc
                WHERE gc.depth = 2
                AND {$categoriesql}
                    AND {$fieldsql} LIKE " .
                        $DB->sql_concat("'%/'", 'gc.id', "'/%'") . "
            )
        ";

        switch ($operator) {
            case self::CONTAINS:
            case self::IS_EQUAL_TO:
            case self::STARTS_WITH:
            case self::ENDS_WITH:
                return [$existssql, $params];

            case self::DOES_NOT_CONTAIN:
            case self::IS_NOT_EQUAL_TO:
                return [
                    "NOT ({$existssql})",
                    $params,
                ];
        }

        return ['', []];
    }
}
