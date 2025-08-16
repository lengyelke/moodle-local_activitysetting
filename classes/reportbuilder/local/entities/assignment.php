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


use html_writer;
use lang_string;
use mod_assign\assign;
use core_reportbuilder\local\filters\{boolean_select, date, duration, text, select};
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;

/**
 * Class assignment
 *
 * This entity represents an assignment activity setting in the report.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel - lengyelke@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment extends base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'assign',
            'groupings',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('assignmentsetting', 'local_activitysetting');
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

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $columns = [];

        $assignalias = $this->get_table_alias('assign');
        $groupingalias = $this->get_table_alias('groupings');

        $this->add_join("LEFT JOIN {groupings} {$groupingalias}
                        ON {$groupingalias}.id = {$assignalias}.teamsubmissiongroupingid
                        AND {$groupingalias}.courseid = {$assignalias}.course");

        // General columns
        // Assignment name column.
        $columns[] = (new column(
            'assignmentname',
            new lang_string('assignmentname', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.name");

        // Assignment submissionattachments column.
        $columns[] = (new column(
            'submissionattachments',
            new lang_string('submissionattachments', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.submissionattachments")
            ->add_callback([format::class, 'boolean_as_text']);

        // Availability columns
        // Assignment allowsubmissionsfromdate column.
        $columns[] = (new column(
            'allowsubmissionsfromdate',
            new lang_string('allowsubmissionsfromdate', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.allowsubmissionsfromdate")
            ->add_callback([format::class, 'userdate']);

        // Assignment duedate column.
        $columns[] = (new column(
            'duedate',
            new lang_string('duedate', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.duedate")
            ->add_callback([format::class, 'userdate']);

        // Assignment cutoffdate column.
        $columns[] = (new column(
            'cutoffdate',
            new lang_string('cutoffdate', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.cutoffdate")
            ->add_callback([format::class, 'userdate']);

        // Assignment gradingduedate column.
        $columns[] = (new column(
            'gradingduedate',
            new lang_string('gradingduedate', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.gradingduedate")
            ->add_callback([format::class, 'userdate']);

        // Assignment timelimit column.
        $columns[] = (new column(
            'timelimit',
            new lang_string('timelimit', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.timelimit")
            ->add_callback([format::class, 'format_time'], 2);

        // Assignment alwaysshowdescription column.
        $columns[] = (new column(
            'submissionattachments',
            new lang_string('alwaysshowdescription', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.alwaysshowdescription")
            ->add_callback([format::class, 'boolean_as_text']);

        // Submission settings columns
        // Assignment submissiondrafts column.
        $columns[] = (new column(
            'submissiondrafts',
            new lang_string('submissiondrafts', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.submissiondrafts")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment requiresubmissionstatement column.
        $columns[] = (new column(
            'requiresubmissionstatement',
            new lang_string('requiresubmissionstatement', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.requiresubmissionstatement")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment maxattempts column.
        $columns[] = (new column(
            'maxattempts',
            new lang_string('maxattempts', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.maxattempts")
            ->add_callback(function ($value) {
                if ((int)$value === -1) {
                    return get_string('unlimitedattempts', 'mod_assign');
                }
                return $value;
            });

        // Assignment attemptreopenmethod column.
        $columns[] = (new column(
            'attemptreopenmethod',
            new lang_string('attemptreopenmethod', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.attemptreopenmethod")
            ->add_callback(static function (string $attemptreopenmethod): string {
                $modes = [
                    ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL => new lang_string('attemptreopenmethod_manual', 'mod_assign'),
                    ASSIGN_ATTEMPT_REOPEN_METHOD_AUTOMATIC => new lang_string('attemptreopenmethod_automatic', 'mod_assign'),
                    ASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS => new lang_string('attemptreopenmethod_untilpass', 'mod_assign'),
                ];

                return (string) ($modes[$attemptreopenmethod] ?? $attemptreopenmethod);
            });

        // Group submission settings columns
        // Assignment teamsubmission column.
        $columns[] = (new column(
            'teamsubmission',
            new lang_string('teamsubmission', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.teamsubmission")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment preventsubmissionnotingroup column.
        $columns[] = (new column(
            'preventsubmissionnotingroup',
            new lang_string('preventsubmissionnotingroup', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.preventsubmissionnotingroup")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment requireallteammemberssubmit column.
        $columns[] = (new column(
            'requireallteammemberssubmit',
            new lang_string('requireallteammemberssubmit', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.requireallteammemberssubmit")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment teamsubmissiongrouping name column.
        $columns[] = (new column(
            'teamsubmissiongroupingname',
            new lang_string('teamsubmissiongroupingid', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$groupingalias}.name")
            ->add_callback(static function ($value): string {
                return $value ?: get_string('none');
            });

        // Notification settings columns
        // Assignment sendnotifications column.
        $columns[] = (new column(
            'sendnotifications',
            new lang_string('sendnotifications', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.sendnotifications")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment sendlatenotifications column.
        $columns[] = (new column(
            'sendlatenotifications',
            new lang_string('sendlatenotifications', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.sendlatenotifications")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment sendstudentnotifications column.
        $columns[] = (new column(
            'sendstudentnotifications',
            new lang_string('sendstudentnotifications', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.sendstudentnotifications")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment blindmarking column.
        $columns[] = (new column(
            'blindmarking',
            new lang_string('blindmarking', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.blindmarking")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment hidegrader column.
        $columns[] = (new column(
            'hidegrader',
            new lang_string('hidegrader', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.hidegrader")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment markingworkflow column.
        $columns[] = (new column(
            'markingworkflow',
            new lang_string('markingworkflow', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.markingworkflow")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment markingallocation column.
        $columns[] = (new column(
            'markingallocation',
            new lang_string('markingallocation', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.markingallocation")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment markinganonymous column.
        $columns[] = (new column(
            'markinganonymous',
            new lang_string('markinganonymous', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.markinganonymous")
            ->add_callback([format::class, 'boolean_as_text']);

        // Unknown columns
        // Assignment revealidentities column.
        $columns[] = (new column(
            'revealidentities',
            new lang_string('revealidentities', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.revealidentities")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment nosubmissions column.
        $columns[] = (new column(
            'nosubmissions',
            new lang_string('assignmentplugins', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.nosubmissions")
            ->add_callback([format::class, 'boolean_as_text']);

        // Assignment completionsubmit column.
        $columns[] = (new column(
            'completionsubmit',
            new lang_string('completionsubmit', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.completionsubmit")
            ->add_callback([format::class, 'boolean_as_text']);

        // Non UI columns
        // Assignment timemodified column.
        $columns[] = (new column(
            'timemodified',
            new lang_string('timemodified', 'mod_assign'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$assignalias}.timemodified")
            ->add_callback([format::class, 'userdate']);

        return $columns;
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

        $assignalias = $this->get_table_alias('assign');
        $groupingalias = $this->get_table_alias('groupings');

        $this->add_join("LEFT JOIN {groupings} {$groupingalias}
                        ON {$groupingalias}.id = {$assignalias}.teamsubmissiongroupingid
                        AND {$groupingalias}.courseid = {$assignalias}.course");

        // Assignment name filter.
        $filters[] = (new filter(
            text::class,
            'assignmentname',
            new lang_string('assignmentname', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.name"
        ))
            ->add_joins($this->get_joins());

        // Assignment submissionattachments filter.
        $filters[] = (new filter(
            boolean_select::class,
            'submissionattachments',
            new lang_string('submissionattachments', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.submissionattachments"
        ))
            ->add_joins($this->get_joins());

        // Assignment allowsubmissionsfromdate filter.
        $filters[] = (new filter(
            date::class,
            'allowsubmissionsfromdate',
            new lang_string('allowsubmissionsfromdate', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.allowsubmissionsfromdate"
        ))
            ->add_joins($this->get_joins());

        // Assignment duedate filter.
        $filters[] = (new filter(
            date::class,
            'duedate',
            new lang_string('duedate', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.duedate"
        ))
            ->add_joins($this->get_joins());

        // Assignment cutoffdate filter.
        $filters[] = (new filter(
            date::class,
            'cutoffdate',
            new lang_string('cutoffdate', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.cutoffdate"
        ))
            ->add_joins($this->get_joins());

        // Assignment gradingduedate filter.
        $filters[] = (new filter(
            date::class,
            'gradingduedate',
            new lang_string('gradingduedate', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.gradingduedate"
        ))
            ->add_joins($this->get_joins());

        // Assignment timelimit filter.
        $filters[] = (new filter(
            duration::class,
            'timelimit',
            new lang_string('timelimit', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.timelimit"
        ))
            ->add_joins($this->get_joins());

        // Assignment alwaysshowdescription filter.
        $filters[] = (new filter(
            boolean_select::class,
            'alwaysshowdescription',
            new lang_string('alwaysshowdescription', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.nosubmissions"
        ))
            ->add_joins($this->get_joins());

        // Assignment submissiondrafts filter.
        $filters[] = (new filter(
            boolean_select::class,
            'submissiondrafts',
            new lang_string('submissiondrafts', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.submissiondrafts"
        ))
            ->add_joins($this->get_joins());

        // Assignment requiresubmissionstatement filter.
        $filters[] = (new filter(
            boolean_select::class,
            'requiresubmissionstatement',
            new lang_string('requiresubmissionstatement', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.requiresubmissionstatement"
        ))
            ->add_joins($this->get_joins());

        // Assignment maxattempts filter.
        $filters[] = (new filter(
            select::class,
            'maxattempts',
            new lang_string('maxattempts', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.maxattempts"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                -1 => new lang_string('unlimitedattempts', 'mod_assign'),
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5',
                6 => '6',
                7 => '7',
                8 => '8',
                9 => '9',
                10 => '10',
                11 => '11',
                12 => '12',
                13 => '13',
                14 => '14',
                15 => '15',
                16 => '16',
                17 => '17',
                18 => '18',
                19 => '19',
                20 => '20',
                21 => '21',
                22 => '22',
                23 => '23',
                24 => '24',
                25 => '25',
                26 => '26',
                27 => '27',
                28 => '28',
                29 => '29',
                30 => '30',

            ]);
        // Assignment attemptreopenmethod filter.
        $filters[] = (new filter(
            select::class,
            'attemptreopenmethod',
            new lang_string('attemptreopenmethod', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.attemptreopenmethod"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL => new lang_string('attemptreopenmethod_manual', 'mod_assign'),
                ASSIGN_ATTEMPT_REOPEN_METHOD_AUTOMATIC => new lang_string('attemptreopenmethod_automatic', 'mod_assign'),
                ASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS => new lang_string('attemptreopenmethod_untilpass', 'mod_assign'),
            ]);

        // Assignment teamsubmission filter.
        $filters[] = (new filter(
            boolean_select::class,
            'teamsubmission',
            new lang_string('teamsubmission', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.teamsubmission"
        ))
            ->add_joins($this->get_joins());

        // Assignment preventsubmissionnotingroup filter.
        $filters[] = (new filter(
            boolean_select::class,
            'preventsubmissionnotingroup',
            new lang_string('preventsubmissionnotingroup', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.preventsubmissionnotingroup"
        ))
            ->add_joins($this->get_joins());

        // Assignment requireallteammemberssubmit filter.
        $filters[] = (new filter(
            boolean_select::class,
            'requireallteammemberssubmit',
            new lang_string('requireallteammemberssubmit', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.requireallteammemberssubmit"
        ))
            ->add_joins($this->get_joins());

        // Assignment teamsubmissiongrouping name filter.
        $filters[] = (new filter(
            text::class,
            'teamsubmissiongroupingname',
            new lang_string('teamsubmissiongroupingid', 'mod_assign'),
            $this->get_entity_name(),
            "{$groupingalias}.name"
        ))
            ->add_joins($this->get_joins());

        // Assignment sendnotifications filter.
        $filters[] = (new filter(
            boolean_select::class,
            'sendnotifications',
            new lang_string('sendnotifications', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.sendnotifications"
        ))
            ->add_joins($this->get_joins());

        // Assignment sendlatenotifications filter.
        $filters[] = (new filter(
            boolean_select::class,
            'sendlatenotifications',
            new lang_string('sendlatenotifications', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.sendlatenotifications"
        ))
            ->add_joins($this->get_joins());

        // Assignment sendstudentnotifications filter.
        $filters[] = (new filter(
            boolean_select::class,
            'sendstudentnotifications',
            new lang_string('sendstudentnotifications', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.sendstudentnotifications"
        ))
            ->add_joins($this->get_joins());

        // Assignment blindmarking filter.
        $filters[] = (new filter(
            boolean_select::class,
            'blindmarking',
            new lang_string('blindmarking', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.blindmarking"
        ))
            ->add_joins($this->get_joins());

        // Assignment hidegrader filter.
        $filters[] = (new filter(
            boolean_select::class,
            'hidegrader',
            new lang_string('hidegrader', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.hidegrader"
        ))
            ->add_joins($this->get_joins());

        // Assignment markingworkflow filter.
        $filters[] = (new filter(
            boolean_select::class,
            'markingworkflow',
            new lang_string('markingworkflow', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.markingworkflow"
        ))
            ->add_joins($this->get_joins());

        // Assignment markingallocation filter.
        $filters[] = (new filter(
            boolean_select::class,
            'markingallocation',
            new lang_string('markingallocation', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.markingallocation"
        ))
            ->add_joins($this->get_joins());

        // Assignment markinganonymous filter.
        $filters[] = (new filter(
            boolean_select::class,
            'markinganonymous',
            new lang_string('markinganonymous', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.markinganonymous"
        ))
            ->add_joins($this->get_joins());

        // Assignment revealidentities filter.
        $filters[] = (new filter(
            boolean_select::class,
            'revealidentities',
            new lang_string('revealidentities', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.revealidentities"
        ))
            ->add_joins($this->get_joins());

        // Assignment completionsubmit filter.
        $filters[] = (new filter(
            boolean_select::class,
            'completionsubmit',
            new lang_string('completionsubmit', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.completionsubmit"
        ))
            ->add_joins($this->get_joins());

        // Assignment nosubmissions filter.
        $filters[] = (new filter(
            boolean_select::class,
            'nosubmissions',
            new lang_string('assignmentplugins', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.nosubmissions"
        ))
            ->add_joins($this->get_joins());

        // Assignment timemodified filter.
        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('timemodified', 'mod_assign'),
            $this->get_entity_name(),
            "{$assignalias}.timemodified"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

    /**
     * Return list of all available conditions - not used - same as filters
     *
     * @return array
     */
    protected function get_all_conditions(): array {

        $conditions = [];

        return $conditions;
    }
}
