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
use question_engine;
use core_reportbuilder\local\filters\{boolean_select, date, duration, text, select, number};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use mod_quiz\question\display_options;
use tool_brickfield\local\areas\mod_choice\option;

/**
 * Class quiz
 *
 * This entity represents a quiz activity setting in the report.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scorm extends base {

    /**
     * Database tables that this entity uses
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'scorm',
            ];
    }

    /**
     * Default title for this entity
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('scormsetting', 'local_activitysetting');
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

        require_once($CFG->dirroot . '/mod/scorm/lib.php');
        require_once("$CFG->dirroot/mod/scorm/locallib.php");

        $scormalias = $this->get_table_alias('scorm');

        $columns = [];

        // General settings.
        // Scorm name.
        $columns[] = (new column(
            'scormname',
            new lang_string('name', 'core'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.name");

        // Package settings .
        // Scorm type.
        $columns[] = (new column(
            'scormtype',
            new lang_string('type', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.scormtype");

        // Scorm reference.
        $columns[] = (new column(
            'scormreference',
            new lang_string('package', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.reference");

        // Scorm version.
        $columns[] = (new column(
            'scormversion',
            new lang_string('scormversion', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.version");

        // Scorm auto update frequency.
        $columns[] = (new column(
            'scormupdatefrequency',
            new lang_string('updatefreq', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.updatefreq")
            ->add_callback(function($value) {
                $options = scorm_get_updatefreq_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });

        // Appearance settings.
        // Scorm display package.
        $columns[] = (new column(
            'scormdisplaypackage',
            new lang_string('display', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.popup")
            ->add_callback(function($value) {
                $options = scorm_get_popup_display_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });

        // Student skip content structure page.
        $columns[] = (new column(
            'scormskipcontentstructure',
            new lang_string('skipview', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.skipview")
            ->add_callback(function($value) {
                $options = scorm_get_skip_view_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });
        // Scorm Disable preview mode.
        $columns[] = (new column(
            'scormdisablepreview',
            new lang_string('hidebrowse', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.hidebrowse")
            ->add_callback([format::class, 'boolean_as_text']);

        // Scorm Display content structure in player.
        $columns[] = (new column(
            'scormdisplaycontentstructure',
            new lang_string('hidetoc', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.hidetoc")
            ->add_callback(function($value) {
                $options = scorm_get_hidetoc_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });

        // Scorm Show Navigation.
        $columns[] = (new column(
            'scormshownavigation',
            new lang_string('nav', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.nav", "nav")
            ->add_field("{$scormalias}.hidetoc", "hidetoc")
            ->add_callback(function($value, $row) {
                $options = scorm_get_navigation_display_array();
                if ($row->hidetoc == SCORM_TOC_SIDE) {
                    return $options[$value] ?? get_string('notset', 'local_activitysetting');
                }
                return get_string('notset', 'local_activitysetting');
            });

        // Scorm Display content structure on entry page.
        $columns[] = (new column(
            'scormdisplaycontentstructureentry',
            new lang_string('displaycoursestructure', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.displaycoursestructure")
            ->add_callback([format::class, 'boolean_as_text']);

        // Scorm Display attempt status.
        $columns[] = (new column(
            'scormdisplayattemptstatus',
            new lang_string('displayattemptstatus', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.displayattemptstatus")
            ->add_callback(function($value) {
                $options = scorm_get_attemptstatus_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });

        // Availability settings.
        // Scorm availability from.
        $columns[] = (new column(
            'scormavailablefrom',
            new lang_string('scormopen', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.timeopen")
            ->add_callback([format::class, 'userdate']);

        // Scorm availability to.
        $columns[] = (new column(
            'scormavailableto',
            new lang_string('scormclose', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.timeclose")
            ->add_callback([format::class, 'userdate']);

        // Attempts management.
        // Scorm number of attempts.
        $columns[] = (new column(
            'scormattempts',
            new lang_string('attempts', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.maxattempt")
            ->add_callback(function($value) {
                $options = scorm_get_attempts_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });

        // Scorm attempts grading .
        $columns[] = (new column(
            'scormattemptsgrading',
            new lang_string('whatgrade', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.whatgrade")
            ->add_callback(function($value) {
                $options = scorm_get_what_grade_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });

        // Scorm Force new attempt.
        $columns[] = (new column(
            'scormforcenewattempt',
            new lang_string('forcenewattempts', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.forcenewattempt")
            ->add_callback(function($value) {
                $options = scorm_get_forceattempt_array();
                return $options[$value] ?? get_string('notset', 'local_activitysetting');
            });

        // Scorm Lock after final attempt.
        $columns[] = (new column(
            'scormlockafterfinalattempt',
            new lang_string('lastattemptlock', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.lastattemptlock")
            ->add_callback([format::class, 'boolean_as_text']);

        // Compatibility settings.
        // Scorm Force completed.
        $columns[] = (new column(
            'scormforcecompleted',
            new lang_string('forcecompleted', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.forcecompleted")
            ->add_callback([format::class, 'boolean_as_text']);

        // Scorm Auto-continue.
        $columns[] = (new column(
            'scormautocontinue',
            new lang_string('autocontinue', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.auto")
            ->add_callback([format::class, 'boolean_as_text']);

        // Scorm Auto-commit.
        $columns[] = (new column(
            'scormautocommit',
            new lang_string('autocommit', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.autocommit")
            ->add_callback([format::class, 'boolean_as_text']);

        // Scorm Mastery score overrides status.
        $columns[] = (new column(
            'scormmasteryoverride',
            new lang_string('masteryoverride', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.masteryoverride")
            ->add_callback([format::class, 'boolean_as_text']);

        // Scorm Require minimum score.
        $columns[] = (new column(
            'scormrequirescore',
            new lang_string('completionscorerequired', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.completionscorerequired")
            ->add_callback(function($value) {
                return $value ?? get_string('notset', 'local_activitysetting');
            });

        // Scorm completion status required.
        $columns[] = (new column(
            'scormcompletionstatus',
            new lang_string('completionstatus_completed', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.completionstatusrequired")
            ->add_callback([format::class, 'boolean_as_text']);

        // Scorm Completion status all sco.
        $columns[] = (new column(
            'scormcompletionstatusallsco',
            new lang_string('completionstatusallscos', 'scorm'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$scormalias}.completionstatusallscos")
            ->add_callback([format::class, 'boolean_as_text']);

        return $columns;
    }

    /**
     * All report filters.
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $scormalias = $this->get_table_alias('scorm');

        $filters = [];

        // Scorm name filter.
        $filters[] = (new filter(
            text::class,
            'scormname',
            new lang_string('name', 'core'),
            $this->get_entity_name(),
            "{$scormalias}.name"
        ))
            ->add_joins($this->get_joins());

        // Scorm type filter.
        $filters[] = (new filter(
            text::class,
            'scormtype',
            new lang_string('type', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.scormtype"
        ))
            ->add_joins($this->get_joins());

        // Scorm reference filter.
        $filters[] = (new filter(
            text::class,
            'scormreference',
            new lang_string('package', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.reference"
        ))
            ->add_joins($this->get_joins());

        // Scorm version filter.
        $filters[] = (new filter(
            text::class,
            'scormversion',
            new lang_string('scormversion', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$scormalias}.version"
        ))
            ->add_joins($this->get_joins());

        // Scorm auto update frequency filter.
        $filters[] = (new filter(
            select::class,
            'scormupdatefrequency',
            new lang_string('updatefreq', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.updatefreq"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_updatefreq_array());

        // Scorm display package filter.
        $filters[] = (new filter(
            select::class,
            'scormdisplaypackage',
            new lang_string('display', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.popup"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_popup_display_array());

        // Student skip content structure page filter.
        $filters[] = (new filter(
            select::class,
            'scormskipcontentstructure',
            new lang_string('skipview', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.skipview"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_skip_view_array());

        // Scorm Disable preview mode filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormdisablepreview',
            new lang_string('hidebrowse', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.hidebrowse"
        ))
            ->add_joins($this->get_joins());

        // Scorm Display content structure in player filter.
        $filters[] = (new filter(
            select::class,
            'scormdisplaycontentstructure',
            new lang_string('hidetoc', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.hidetoc"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_hidetoc_array());

        // Scorm Show Navigation filter.
        $filters[] = (new filter(
            select::class,
            'scormshownavigation',
            new lang_string('nav', 'scorm'),
            $this->get_entity_name(),
            "CASE
                WHEN {$scormalias}.hidetoc = " . SCORM_TOC_SIDE . "
                THEN {$scormalias}.nav
                ELSE 999
            END"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                $options = scorm_get_navigation_display_array();
                $options[999] = get_string('notset', 'local_activitysetting');
                return $options;
            });

        // Scorm Display content structure on entry page filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormdisplaycontentstructureentry',
            new lang_string('displaycoursestructure', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.displaycoursestructure"
        ))
            ->add_joins($this->get_joins());

        // Scorm Display attempt status filter.
        $filters[] = (new filter(
            select::class,
            'scormdisplayattemptstatus',
            new lang_string('displayattemptstatus', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.displayattemptstatus"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_attemptstatus_array());

        // Scorm availability from filter.
        $filters[] = (new filter(
            date::class,
            'scormavailablefrom',
            new lang_string('scormopen', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.timeopen"
        ))
            ->add_joins($this->get_joins());

        // Scorm availability to filter.
        $filters[] = (new filter(
            date::class,
            'scormavailableto',
            new lang_string('scormclose', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.timeclose"
        ))
            ->add_joins($this->get_joins());

        // Scorm number of attempts filter.
        $filters[] = (new filter(
            select::class,
            'scormattempts',
            new lang_string('attempts', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.maxattempt"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_attempts_array());

        // Scorm attempts grading filter.
        $filters[] = (new filter(
            select::class,
            'scormattemptsgrading',
            new lang_string('whatgrade', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.whatgrade"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_what_grade_array());

        // Scorm Force new attempt filter.
        $filters[] = (new filter(
            select::class,
            'scormforcenewattempt',
            new lang_string('forcenewattempts', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.forcenewattempt"
        ))
            ->add_joins($this->get_joins())
            ->set_options(scorm_get_forceattempt_array());

        // Scorm Lock after final attempt filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormlockafterfinalattempt',
            new lang_string('lastattemptlock', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.lastattemptlock"
        ))
            ->add_joins($this->get_joins());

        // Scorm Force completed filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormforcecompleted',
            new lang_string('forcecompleted', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.forcecompleted"
        ))
            ->add_joins($this->get_joins());

        // Scorm Auto-continue filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormautocontinue',
            new lang_string('autocontinue', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.auto"
        ))
            ->add_joins($this->get_joins());

        // Scorm Auto-commit filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormautocommit',
            new lang_string('autocommit', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.autocommit"
        ))
            ->add_joins($this->get_joins());

        // Scorm Mastery score overrides status filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormmasteryoverride',
            new lang_string('masteryoverride', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.masteryoverride"
        ))
            ->add_joins($this->get_joins());

        // Scorm Require minimum score filter.
        $filters[] = (new filter(
            number::class,
            'scormrequirescore',
            new lang_string('completionscorerequired', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.completionscorerequired"
        ))
            ->add_joins($this->get_joins());

        // Scorm Completion status required filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormcompletionstatus',
            new lang_string('completionstatus_completed', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.completionstatusrequired"
        ))
            ->add_joins($this->get_joins());

        // Scorm Completion status all sco filter.
        $filters[] = (new filter(
            boolean_select::class,
            'scormcompletionstatusallsco',
            new lang_string('completionstatusallscos', 'scorm'),
            $this->get_entity_name(),
            "{$scormalias}.completionstatusallscos"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
