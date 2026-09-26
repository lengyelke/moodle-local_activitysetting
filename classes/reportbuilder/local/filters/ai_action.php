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

namespace local_activitysetting\reportbuilder\local\filters;

use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\database;

/**
 * Filter for AI actions stored in JSON.
 *
 * @package    local_activitysetting
 * @copyright  2026 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_action extends select {
    /**
     * Provide the options for the select dropdown.
     * Overriding this method allows us to dynamically fetch AI actions.
     *
     * @return array
     */
    protected function get_select_options(): array {
        $options = [];

        $placements = \core_component::get_plugin_list('aiplacement');

        foreach (array_keys($placements) as $placement) {
            $pluginname = 'aiplacement_' . $placement;

            $actions = \core_ai\manager::get_supported_actions($pluginname);

            foreach ($actions as $actionclass) {
                $action = substr($actionclass, strrpos($actionclass, '\\') + 1);

                $label = get_string('action_' . $action, 'ai');
                $options[$action] = ($label === 'action_' . $action)
                    ? ucfirst(str_replace('_', ' ', $action))
                    : $label;
            }
        }

        asort($options);

        return $options;
    }

    /**
     * Return filter SQL.
     *
     * @param array $values
     * @return array Array containing SQL and parameters.
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $operator = (int) ($values["{$this->name}_operator"] ?? self::ANY_VALUE);
        $value = (string) ($values["{$this->name}_value"] ?? '');

        // No filtering required.
        if ($operator === self::ANY_VALUE || $value === '') {
            return ['', []];
        }

        // Ensure the submitted value is one of our available AI actions.
        if (!array_key_exists($value, $this->get_select_options())) {
            return ['', []];
        }

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $paramname = database::generate_param_name();

        $pattern = '%"' . $DB->sql_like_escape($value) . '":1%';

        switch ($operator) {
            case self::EQUAL_TO:
                $sql = $DB->sql_like($fieldsql, ":$paramname", false);
                break;

            case self::NOT_EQUAL_TO:
                $sql = $DB->sql_like($fieldsql, ":$paramname", false, true);
                break;

            default:
                return ['', []];
        }

        $params[$paramname] = $pattern;

        return [$sql, $params];
    }

    /**
     * Return the available comparison operators.
     *
     * @return array
     */
    protected function get_operators(): array {
        return [
            self::ANY_VALUE => get_string('filterisanyvalue', 'core_reportbuilder'),
            self::EQUAL_TO => get_string('contains', 'local_activitysetting'),
            self::NOT_EQUAL_TO => get_string('doesnotcontain', 'local_activitysetting'),
        ];
    }
}
