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
 * Class forum
 *
 * This entity represents a Forum activity setting in the report.
 *
 * @package    local_activitysetting
 * @copyright  2025 Ferenc 'Frank' Lengyel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum extends base {

    /**
     * Database tables that this entity uses
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['forum'];
    }

    /**
     * Default title for this entity
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('forumsetting', 'local_activitysetting');
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
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        $forumalias = $this->get_table_alias('forum');
        $columns = [];

        // Forum name.
        $columns[] = (new column(
            'forumname',
            new lang_string('forumname', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.name");

        // Forum type.
        $columns[] = (new column(
            'type',
            new lang_string('forumtype', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.type")
            ->add_callback(function($value) {
                $strings = [
                    'single' => get_string('singleforum', 'mod_forum'),
                    'eachuser' => get_string('eachuserforum', 'mod_forum'),
                    'qanda' => get_string('qandaforum', 'mod_forum'),
                    'blog' => get_string('blogforum', 'mod_forum'),
                    'general' => get_string('generalforum', 'mod_forum'),
                    'news' => get_string('namenews', 'mod_forum'),
                ];
                return $strings[$value] ?? $value;
            });

        // Due date.
        $columns[] = (new column(
            'duedate',
            new lang_string('duedate', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.duedate")
            ->add_callback([format::class, 'userdate']);

        // Cut-off date.
        $columns[] = (new column(
            'cutoffdate',
            new lang_string('cutoffdate', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.cutoffdate")
            ->add_callback([format::class, 'userdate']);

        // Max attachments.
        $columns[] = (new column(
            'maxattachments',
            new lang_string('maxattachments', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.maxattachments");

        // Max bytes (attachment size).
        $columns[] = (new column(
            'maxbytes',
            new lang_string('maxattachmentsize', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.maxbytes", "maxbytes")
            ->add_field("{$forumalias}.course", "cid")
            ->add_callback(function($value, $row) use ($DB, $CFG) {
                // If the value is 1, it means not allowed.
                if ($value == 1) {
                    return get_string('uploadnotallowed', 'core');
                }
                // If the value is 0, it means site/course limit.
                if ($value == 0) {
                    return get_string('uploadsiteorcourse', 'local_activitysetting');
                }
                // Render as human-friendly size.
                return display_size($value);
            });

        // Display word count.
        $columns[] = (new column(
            'displaywordcount',
            new lang_string('displaywordcount', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.displaywordcount")
            ->add_callback([format::class, 'boolean_as_text']);

        // Force subscribe.
        $columns[] = (new column(
            'forcesubscribe',
            new lang_string('subscriptionmode', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.forcesubscribe")
            ->add_callback(function($value) {
                $strings = [
                    FORUM_CHOOSESUBSCRIBE => get_string('subscriptionoptional', 'mod_forum'),
                    FORUM_FORCESUBSCRIBE => get_string('subscriptionforced', 'mod_forum'),
                    FORUM_INITIALSUBSCRIBE => get_string('subscriptionauto', 'mod_forum'),
                    FORUM_DISALLOWSUBSCRIBE => get_string('subscriptiondisabled', 'mod_forum'),
                ];
                return $strings[$value] ?? $value;
            });

        // Read tracking.
        $columns[] = (new column(
            'trackingtype',
            new lang_string('trackingtype', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.trackingtype")
            ->add_callback(function($value) {
                $strings = [
                    FORUM_TRACKING_OFF => get_string('trackingoff', 'mod_forum'),
                    FORUM_TRACKING_OPTIONAL => get_string('trackingoptional', 'mod_forum'),
                    FORUM_TRACKING_FORCED => get_string('trackingon', 'mod_forum'),
                ];
                return $strings[$value] ?? $value;
            });

        // RSS feed type.
        $columns[] = (new column(
            'rsstype',
            new lang_string('rsstype', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.rsstype")
            ->add_callback(function($value) {
                $strings = [
                    0 => get_string('none'),
                    1 => get_string('discussions', 'forum'),
                    2 => get_string('posts', 'forum'),
                ];
                return $strings[$value] ?? $value;
            });

        // RSS max items.
        $columns[] = (new column(
            'rssarticles',
            new lang_string('rssarticles', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.rssarticles");

        // Discussion locking.
        $columns[] = (new column(
            'lockdiscussionafter',
            new lang_string('lockdiscussionafter', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.lockdiscussionafter")
            ->add_callback(static function($value, $row) {
                return ($value == 0) ? get_string('discussionlockingdisabled', 'forum') : format::format_time($value, $row);
            });

        // Block period.
        $columns[] = (new column(
            'blockperiod',
            new lang_string('blockperiod', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.blockperiod")
            ->add_callback(static function($value, $row) {
                return ($value == 0) ? get_string('blockperioddisabled', 'forum') : format::format_time($value, $row);
            });

        // Block after.
        $columns[] = (new column(
            'blockafter',
            new lang_string('blockafter', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.blockafter");

        // Warn after.
        $columns[] = (new column(
            'warnafter',
            new lang_string('warnafter', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.warnafter");

        // Notify students.
        $columns[] = (new column(
            'notifystudents',
            new lang_string('sendstudentnotificationsdefault', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.grade_forum_notify")
            ->add_callback([format::class, 'boolean_as_text']);

        // Aggregate type.
        $columns[] = (new column(
            'aggregatetype',
            new lang_string('aggregatetype', 'rating'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.assessed")
            ->add_callback(function($value) {
                $strings = [
                    RATING_AGGREGATE_NONE => get_string('aggregatenone', 'rating'),
                    RATING_AGGREGATE_AVERAGE => get_string('aggregateavg', 'rating'),
                    RATING_AGGREGATE_COUNT => get_string('aggregatecount', 'rating'),
                    RATING_AGGREGATE_MAXIMUM => get_string('aggregatemax', 'rating'),
                    RATING_AGGREGATE_MINIMUM => get_string('aggregatemin', 'rating'),
                    RATING_AGGREGATE_SUM => get_string('aggregatesum', 'rating'),
                ];
                return $strings[$value] ?? $value;
            });

        // Rating start.
        $columns[] = (new column(
            'ratingstart',
            new lang_string('ratingstart', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.assesstimestart")
            ->add_callback([format::class, 'userdate']);

        // Rating end.
        $columns[] = (new column(
            'ratingend',
            new lang_string('ratingend', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.assesstimefinish")
            ->add_callback([format::class, 'userdate']);

        // Completion posts.
        $columns[] = (new column(
            'completionposts',
            new lang_string('completionposts', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.completionposts");

        // Completion discussions.
        $columns[] = (new column(
            'completiondiscussions',
            new lang_string('completiondiscussions', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.completiondiscussions");

        // Completion replies.
        $columns[] = (new column(
            'completionreplies',
            new lang_string('completionreplies', 'mod_forum'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.completionreplies");

        // Timemodified (last updated).
        $columns[] = (new column(
            'timemodified',
            new lang_string('timemodified', 'core_reportbuilder'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_field("{$forumalias}.timemodified")
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * All report filters.
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $forumalias = $this->get_table_alias('forum');
        $filters = [];

        // Forum name filter.
        $filters[] = (new filter(
            text::class,
            'forumname',
            new lang_string('forumname', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.name"
        ));

        // Forum type filter.
        $filters[] = (new filter(
            select::class,
            'type',
            new lang_string('forumtype', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.type"
        ))->set_options([
            'single' => get_string('singleforum', 'mod_forum'),
            'eachuser' => get_string('eachuserforum', 'mod_forum'),
            'qanda' => get_string('qandaforum', 'mod_forum'),
            'blog' => get_string('blogforum', 'mod_forum'),
            'general' => get_string('generalforum', 'mod_forum'),
            'news' => get_string('namenews', 'mod_forum'),
        ]);

        // Due date filter.
        $filters[] = (new filter(
            date::class,
            'duedate',
            new lang_string('duedate', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.duedate"
        ));

        // Cut-off date filter.
        $filters[] = (new filter(
            date::class,
            'cutoffdate',
            new lang_string('cutoffdate', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.cutoffdate"
        ));

        // Max attachments filter.
        $filters[] = (new filter(
            number::class,
            'maxattachments',
            new lang_string('maxattachments', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.maxattachments"
        ));

        // Display word count filter.
        $filters[] = (new filter(
            boolean_select::class,
            'displaywordcount',
            new lang_string('displaywordcount', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.displaywordcount"
        ));

        // Force subscribe filter.
        $filters[] = (new filter(
            select::class,
            'forcesubscribe',
            new lang_string('subscriptionmode', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.forcesubscribe"
        ))->set_options([
            FORUM_CHOOSESUBSCRIBE => get_string('subscriptionoptional', 'mod_forum'),
            FORUM_FORCESUBSCRIBE => get_string('subscriptionforced', 'mod_forum'),
            FORUM_INITIALSUBSCRIBE => get_string('subscriptionauto', 'mod_forum'),
            FORUM_DISALLOWSUBSCRIBE => get_string('subscriptiondisabled', 'mod_forum'),
        ]);

        // Read tracking filter.
        $filters[] = (new filter(
            select::class,
            'trackingtype',
            new lang_string('trackingtype', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.trackingtype"
        ))->set_options([
            FORUM_TRACKING_OFF => get_string('trackingoff', 'mod_forum'),
            FORUM_TRACKING_OPTIONAL => get_string('trackingoptional', 'mod_forum'),
            FORUM_TRACKING_FORCED => get_string('trackingon', 'mod_forum'),
        ]);

        // RSS feed type filter.
        $filters[] = (new filter(
            select::class,
            'rsstype',
            new lang_string('rsstype', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.rsstype"
        ))->set_options([
            0 => get_string('none'),
            1 => get_string('discussions', 'forum'),
            2 => get_string('posts', 'forum'),
        ]);

        // RSS max items filter.
        $filters[] = (new filter(
            number::class,
            'rssarticles',
            new lang_string('rssarticles', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.rssarticles"
        ));

        // Discussion locking filter.
        $filters[] = (new filter(
            number::class,
            'lockdiscussionafter',
            new lang_string('lockdiscussionafter', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.lockdiscussionafter"
        ));

        // Block period filter.
        $filters[] = (new filter(
            number::class,
            'blockperiod',
            new lang_string('blockperiod', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.blockperiod"
        ));

        // Block after filter.
        $filters[] = (new filter(
            number::class,
            'blockafter',
            new lang_string('blockafter', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.blockafter"
        ));

        // Warn after filter.
        $filters[] = (new filter(
            number::class,
            'warnafter',
            new lang_string('warnafter', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.warnafter"
        ));

        // Notify students filter.
        $filters[] = (new filter(
            boolean_select::class,
            'notifystudents',
            new lang_string('sendstudentnotificationsdefault', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.grade_forum_notify"
        ));

        // Aggregate type filter.
        $filters[] = (new filter(
            select::class,
            'aggregatetype',
            new lang_string('aggregatetype', 'rating'),
            $this->get_entity_name(),
            "{$forumalias}.assessed"
        ))->set_options([
            RATING_AGGREGATE_NONE => get_string('aggregatenone', 'rating'),
            RATING_AGGREGATE_AVERAGE => get_string('aggregateavg', 'rating'),
            RATING_AGGREGATE_COUNT => get_string('aggregatecount', 'rating'),
            RATING_AGGREGATE_MAXIMUM => get_string('aggregatemax', 'rating'),
            RATING_AGGREGATE_MINIMUM => get_string('aggregatemin', 'rating'),
            RATING_AGGREGATE_SUM => get_string('aggregatesum', 'rating'),
        ]);

        // Rating start filter.
        $filters[] = (new filter(
            date::class,
            'ratingstart',
            new lang_string('ratingstart', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$forumalias}.assesstimestart"
        ));

        // Rating end filter.
        $filters[] = (new filter(
            date::class,
            'ratingend',
            new lang_string('ratingend', 'local_activitysetting'),
            $this->get_entity_name(),
            "{$forumalias}.assesstimefinish"
        ));

        // Completion posts filter.
        $filters[] = (new filter(
            number::class,
            'completionposts',
            new lang_string('completionposts', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.completionposts"
        ));

        // Completion discussions filter.
        $filters[] = (new filter(
            number::class,
            'completiondiscussions',
            new lang_string('completiondiscussions', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.completiondiscussions"
        ));

        // Completion replies filter.
        $filters[] = (new filter(
            number::class,
            'completionreplies',
            new lang_string('completionreplies', 'mod_forum'),
            $this->get_entity_name(),
            "{$forumalias}.completionreplies"
        ));

        // Timemodified filter.
        $filters[] = (new filter(
            date::class,
            'timemodified',
            new lang_string('timemodified', 'core_reportbuilder'),
            $this->get_entity_name(),
            "{$forumalias}.timemodified"
        ));

        return $filters;
    }
}
