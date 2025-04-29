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
use core_reportbuilder\local\filters\{boolean_select, date, text, select, number};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;

/**
 * Class quiz
 *
 * This entity represents a quiz activity setting in the report.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz extends base {

    /**
     * Database tables that this entity uses
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['quiz'];
    }

    /**
     * Default title for this entity
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('quizsetting', 'local_activitysetting');
    }

    /**
     * Initialise the entity.
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
        require_once($CFG->dirroot.'/rating/lib.php');
        require_once($CFG->dirroot.'/mod/quiz/lib.php');

        $quizalias = $this->get_table_alias('quiz');
        $columns = [];

        // Quiz name.
        $columns[] = (new column(
            'quizname',
            new lang_string('name', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.name");

        // Open quiz.
        $columns[] = (new column(
            'quizopen',
            new lang_string('quizopen', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.timeopen")
            ->add_callback([format::class, 'userdate']);

        // Close quiz.
        $columns[] = (new column(
            'quizclose',
            new lang_string('quizclose', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.timeclose")
            ->add_callback([format::class, 'userdate']);

        // Time limit.
        $columns[] = (new column(
            'timelimit',
            new lang_string('timelimit', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.timelimit")
            ->add_callback(function($value, $row) {
                if ($value == 0) {
                    return get_string('notset', 'local_activitysetting');
                } else {
                    return format::format_time($value, $row);
                }
            });

        // Handling overdue attempts.
        $columns[] = (new column(
            'overduehandling',
            new lang_string('overduehandling', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.overduehandling")
            ->add_callback(function($value) {
                $strings = [
                    'autosubmit'  => get_string('overduehandlingautosubmit', 'quiz'),
                    'graceperiod' => get_string('overduehandlinggraceperiod', 'quiz'),
                    'autoabandon' => get_string('overduehandlingautoabandon', 'quiz'),
                ];
                return $strings[$value] ?? $value;
            });

        // Grace period.
        $columns[] = (new column(
            'graceperiod',
            new lang_string('graceperiod', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.graceperiod")
            ->add_callback(function($value, $row) {
                if ($value == 0) {
                    return get_string('notset', 'local_activitysetting');
                } else {
                    return format::format_time($value, $row);
                }
            });

        // Layout.
        $columns[] = (new column(
            'questionsperpage',
            new lang_string('newpage', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.questionsperpage")
            ->add_callback(function($value) {
                $pageoptions = [];
                $pageoptions[0] = get_string('neverallononepage', 'quiz');
                $pageoptions[1] = get_string('everyquestion', 'quiz');
                for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
                    $pageoptions[$i] = get_string('everynquestions', 'quiz', $i);
                }
                return $pageoptions[$value] ?? $value;
            });

        // Navigation method.
        $columns[] = (new column(
            'navmethod',
            new lang_string('navmethod', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.navmethod")
            ->add_callback(function($value) {
                $navoptions = [
                    QUIZ_NAVMETHOD_FREE => get_string('navmethod_free', 'quiz'),
                    QUIZ_NAVMETHOD_SEQ  => get_string('navmethod_seq', 'quiz'),
                ];
                return $navoptions[$value] ?? $value;
            });

        // Shuffle within questions.
        $columns[] = (new column(
            'shufflequestions',
            new lang_string('shufflewithin', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.shuffleanswers")
            ->add_callback(function($value) {
                return $value ? get_string('yes') : get_string('no');
            });

        // Preferred behaviour.
        $columns[] = (new column(
            'preferredbehaviour',
            new lang_string('howquestionsbehave', 'question'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$quizalias}.preferredbehaviour")
            ->add_callback(function($value) {
                return get_string('pluginname', 'qbehaviour_' . $value);
            });

        return $columns;
    }

    /**
     * All report filters.
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $quizalias = $this->get_table_alias('quiz');
        $filters = [];

        // Quiz name filter.
        $filters[] = (new filter(
            text::class,
            'quizname',
            new lang_string('name', 'mod_quiz'),
            $this->get_entity_name(),
            "{$quizalias}.name"
        ));

        return $filters;
    }
}
