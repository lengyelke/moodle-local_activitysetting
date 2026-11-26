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

use MoodleQuickForm;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\filters\base;
use core\lang_string;

/**
 * Class module_selector
 *
 * @package    local_activitysetting
 * @copyright  2026 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_selector extends base {
    /** @var int Any value */
    public const ANY_VALUE = -1;

    /** @var int Filter for selected types */
    public const EQUAL_TO = 0;

    /** @var int Filter for excluded types */
    public const NOT_EQUAL_TO = 1;

    /**
     * Returns an array of comparison operators
     *
     * @return array
     */
    private function get_operators(): array {
        $operators = [
            self::ANY_VALUE => new lang_string('filterisanyvalue', 'core_reportbuilder'),
            self::EQUAL_TO => new lang_string('filterisequalto', 'core_reportbuilder'),
            self::NOT_EQUAL_TO => new lang_string('filterisnotequalto', 'core_reportbuilder'),
        ];

        return $this->filter->restrict_limited_operators($operators);
    }

    /**
     * Add the multi-select module-type control.
     *
     * @param MoodleQuickForm $mform The form to add the control to.
     */
    public function setup_form(MoodleQuickForm $mform): void {
        global $DB;

        $operatorlabel = get_string('filterfieldoperator', 'core_reportbuilder', $this->get_header());
        $mform->addElement('select', "{$this->name}_operator", $operatorlabel, $this->get_operators())
            ->setHiddenLabel(true);

        $valuelabel = get_string(
            'filterfieldvalue',
            'core_reportbuilder',
            $this->get_header()
        );

        $options = [];

        foreach ($DB->get_records('modules', null, 'name ASC') as $module) {
            $options[$module->id] = get_string('modulename', $module->name);
        }

        $mform->addElement(
            'autocomplete',
            $this->name . '_values',
            $valuelabel,
            $options,
            ['multiple' => true]
        )->setHiddenLabel(true);
        $mform->hideIf("{$this->name}_values", "{$this->name}_operator", 'eq', self::ANY_VALUE);
    }

    /**
     * Build the SQL condition.
     *
     * @param array $values The values from the form.
     * @return array An array containing the SQL condition and parameters.
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        [$fieldsql, $params] = $this->filter->get_field_sql_and_params();

        $operator = (int) ($values["{$this->name}_operator"] ?? self::ANY_VALUE);
        $moduleids = $values["{$this->name}_values"] ?? [];

        // Any value is inactive.
        if ($operator === self::ANY_VALUE) {
            return ['', []];
        }

        // No selected module types means no meaningful equality/exclusion filter.
        if (empty($moduleids) || !is_array($moduleids)) {
            return ['', []];
        }

        // Ensure they are integers before binding them.
        $moduleids = array_map('intval', $moduleids);
        $moduleids = array_filter(
            $moduleids,
            static fn(int $id): bool => $id > 0
        );

        if (empty($moduleids)) {
            return ['', []];
        }

        // Build the positive matching condition: cm.module IN (...).
        [$select, $sqlparams] = $DB->get_in_or_equal(
            $moduleids,
            SQL_PARAMS_NAMED,
            database::generate_param_name('_'),
            true
        );

        $params = array_merge($params, $sqlparams);
        $sql = "{$fieldsql} {$select}";

        // Negate the entire positive condition for "Not equal to".
        if ($operator === self::NOT_EQUAL_TO) {
            $sql = "NOT ({$sql})";
        }

        return [$sql, $params];
    }

    /**
     * Values used by Report Builder's sample data / preview features.
     */
    public function get_sample_values(): array {
        global $DB;

        $moduleids = $DB->get_fieldset_sql(
            "SELECT id
            FROM {modules}
            WHERE name IN ('assign', 'quiz')
        ORDER BY id ASC"
        );

        return [
            "{$this->name}_operator" => self::EQUAL_TO,
            "{$this->name}_values" => $moduleids,
        ];
    }
}
