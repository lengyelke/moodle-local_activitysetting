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
use mod_quiz\question\display_options;

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
        return [
            'quiz',
            'quizaccess_seb_quizsettings',
        ];
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
        $quizaccessalias = $this->get_table_alias('quizaccess_seb_quizsettings');

        $this->add_join("LEFT JOIN {quizaccess_seb_quizsettings} $quizaccessalias ON $quizaccessalias.quizid = $quizalias.id");

        $columns = [];

        // Quiz name.
        $columns[] = (new column(
            'quizname',
            new lang_string('name', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.name");

        // Open quiz.
        $columns[] = (new column(
            'quizopen',
            new lang_string('quizopen', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
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
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.preferredbehaviour")
            ->add_callback(function($value) {
                return get_string('pluginname', 'qbehaviour_' . $value);
            });

        // Allow redo.
        $columns[] = (new column(
            'canredoquestions',
            new lang_string('canredoquestions', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.canredoquestions")
            ->add_callback(function($value) {
                return $value ? get_string('yes') : get_string('no');
            });

        // Attempt on last attempt.
        $columns[] = (new column(
            'attemptonlast',
            new lang_string('eachattemptbuildsonthelast', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.attemptonlast")
            ->add_callback(function($value) {
                return $value ? get_string('yes') : get_string('no');
            });

        // Review attemp.
        $columns[] = (new column(
            'reviewattempt',
            new lang_string('reviewattempt', 'mod_quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewattempt")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Review correctness.
        $columns[] = (new column(
            'reviewcorrectness',
            new lang_string('whethercorrect', 'question'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewcorrectness")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Review maxmarks.
        $columns[] = (new column(
            'reviewmaxmarks',
            new lang_string('maxmarks', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewmaxmarks")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Review marks.
        $columns[] = (new column(
            'reviewmarks',
            new lang_string('marks', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewmarks")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Review specific feedback.
        $columns[] = (new column(
            'reviewspecificfeedback',
            new lang_string('specificfeedback', 'question'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewspecificfeedback")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Review general feedback.
        $columns[] = (new column(
            'reviewgeneralfeedback',
            new lang_string('generalfeedback', 'question'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewgeneralfeedback")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Review right answer.
        $columns[] = (new column(
            'reviewrightanswer',
            new lang_string('rightanswer', 'question'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewrightanswer")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Review overall feedback.
        $columns[] = (new column(
            'reviewoverallfeedback',
            new lang_string('reviewoverallfeedback', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.reviewoverallfeedback")
            ->add_callback(function($value) {
                $options = [];

                if ($value & display_options::DURING) {
                    $options[] = get_string('reviewduring', 'quiz');
                }
                if ($value & display_options::IMMEDIATELY_AFTER) {
                    $options[] = get_string('reviewimmediately', 'quiz');
                }
                if ($value & display_options::LATER_WHILE_OPEN) {
                    $options[] = get_string('reviewopen', 'quiz');
                }
                if ($value & display_options::AFTER_CLOSE) {
                    $options[] = get_string('reviewclosed', 'quiz');
                }

                return $options ? implode('; ', $options) : get_string('none');
            });

        // Show user picture.
        $columns[] = (new column(
            'showuserpicture',
            new lang_string('showuserpicture', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.showuserpicture")
            ->add_callback(function($value) {
                return $value ? get_string('yes') : get_string('no');
            });

        // Decimal points.
        $columns[] = (new column(
            'decimalpoints',
            new lang_string('decimalplaces', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.decimalpoints");

        // Question decimal points.
        $columns[] = (new column(
            'questiondecimalpoints',
            new lang_string('decimalplacesquestion', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.questiondecimalpoints")
            ->add_callback(function($value) {
                return $value == -1 ? get_string('sameasoverall', 'quiz') : $value;
            });

        // Require Safe Exam Browser.
        $columns[] = (new column(
            'safeexambrowser',
            new lang_string('seb_requiresafeexambrowser', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.requiresafeexambrowser")
            ->add_callback(static function(?int $requiresafeexambrowser): string {
                $types = [
                    0 => new lang_string('no'),
                    1 => new lang_string('seb_use_manually', 'quizaccess_seb'),
                    2 => new lang_string('seb_use_template', 'quizaccess_seb'),
                    3 => new lang_string('seb_use_upload', 'quizaccess_seb'),
                    4 => new lang_string('seb_use_client', 'quizaccess_seb'),
                ];
                return (string) (
                    $types[$requiresafeexambrowser]
                    ?? ($requiresafeexambrowser === null
                        ? get_string('notset', 'local_activitysetting')
                        : get_string('unknown', 'local_activitysetting')
                    )
                );
            });

        // Safe Exam Browser Yes/No fields.
        $columns[] = (new column(
            'safeexambrowserconfigyesno',
            new lang_string('sebconfigyesno', 'local_activitysetting'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.showsebdownloadlink", "showsebdownloadlink")
            ->add_field("{$quizaccessalias}.userconfirmquit", "userconfirmquit")
            ->add_field("{$quizaccessalias}.allowuserquitseb", "allowuserquitseb")
            ->add_field("{$quizaccessalias}.allowreloadinexam", "allowreloadinexam")
            ->add_field("{$quizaccessalias}.showsebtaskbar", "showsebtaskbar")
            ->add_field("{$quizaccessalias}.showreloadbutton", "showreloadbutton")
            ->add_field("{$quizaccessalias}.showtime", "showtime")
            ->add_field("{$quizaccessalias}.showkeyboardlayout", "showkeyboardlayout")
            ->add_field("{$quizaccessalias}.showwificontrol", "showwificontrol")
            ->add_field("{$quizaccessalias}.enableaudiocontrol", "enableaudiocontrol")
            ->add_field("{$quizaccessalias}.allowspellchecking", "allowspellchecking")
            ->add_field("{$quizaccessalias}.activateurlfiltering", "activateurlfiltering")
            ->add_field("{$quizaccessalias}.muteonstartup", "muteonstartup")
            ->add_field("{$quizaccessalias}.allowcapturecamera", "allowcapturecamera")
            ->add_field("{$quizaccessalias}.allowcapturemicrophone", "allowcapturemicrophone")
            ->add_field("{$quizaccessalias}.filterembeddedcontent", "filterembeddedcontent")

            ->add_callback(function($value, $row) use ($quizaccessalias): string {
                $options = [];
                if ($row->showsebdownloadlink) {
                    $options[] = get_string('seb_showsebdownloadlink', 'quizaccess_seb');
                }
                if ($row->userconfirmquit) {
                    $options[] = get_string('seb_userconfirmquit', 'quizaccess_seb');
                }
                if ($row->allowuserquitseb) {
                    $options[] = get_string('seb_allowuserquitseb', 'quizaccess_seb');
                }
                if ($row->allowreloadinexam) {
                    $options[] = get_string('seb_allowreloadinexam', 'quizaccess_seb');
                }
                if ($row->showsebtaskbar) {
                    $options[] = get_string('seb_showsebtaskbar', 'quizaccess_seb');
                }
                if ($row->showsebtaskbar && $row->showreloadbutton) {
                    $options[] = get_string('seb_showreloadbutton', 'quizaccess_seb');
                }
                if ($row->showsebtaskbar && $row->showtime) {
                    $options[] = get_string('seb_showtime', 'quizaccess_seb');
                }
                if ($row->showsebtaskbar && $row->showkeyboardlayout) {
                    $options[] = get_string('seb_showkeyboardlayout', 'quizaccess_seb');
                }
                if ($row->showsebtaskbar && $row->showwificontrol) {
                    $options[] = get_string('seb_showwificontrol', 'quizaccess_seb');
                }
                if ($row->enableaudiocontrol) {
                    $options[] = get_string('seb_enableaudiocontrol', 'quizaccess_seb');
                }
                if ($row->enableaudiocontrol && $row->muteonstartup) {
                    $options[] = get_string('seb_muteonstartup', 'quizaccess_seb');
                }
                if ($row->allowcapturecamera) {
                    $options[] = get_string('seb_allowcapturecamera', 'quizaccess_seb');
                }
                if ($row->allowcapturemicrophone) {
                    $options[] = get_string('seb_allowcapturemicrophone', 'quizaccess_seb');
                }
                if ($row->allowspellchecking) {
                    $options[] = get_string('seb_allowspellchecking', 'quizaccess_seb');
                }
                if ($row->activateurlfiltering) {
                    $options[] = get_string('seb_activateurlfiltering', 'quizaccess_seb');
                }
                if ($row->activateurlfiltering && $row->filterembeddedcontent) {
                    $options[] = get_string('seb_filterembeddedcontent', 'quizaccess_seb');
                }
                return $options ? implode('; ', $options) : get_string('none');
            });

        // Safe Exam Browser Text fields.
        // Quit password.
        $columns[] = (new column(
            'quitpassword',
            new lang_string('seb_quitpassword', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.quitpassword")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // Link quit SEB.
        $columns[] = (new column(
            'linkquitseb',
            new lang_string('exitsebbutton', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.linkquitseb")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // SEB expressions allowed.
        $columns[] = (new column(
            'sebexpressiosnallowed',
            new lang_string('seb_expressionsallowed', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.expressionsallowed")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // SEB regex allowed.
        $columns[] = (new column(
            'sebregexallowed',
            new lang_string('seb_regexallowed', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.regexallowed")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // SEB expressions blocked.
        $columns[] = (new column(
            'sebexpressionsblocked',
            new lang_string('seb_expressionsblocked', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.expressionsblocked")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // SEB regex blocked.
        $columns[] = (new column(
            'sebregexblocked',
            new lang_string('seb_regexblocked', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.regexblocked")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // SEB allowed browser exam keys.
        $columns[] = (new column(
            'seballowedbrowserexamkeys',
            new lang_string('seb_allowedbrowserexamkeys', 'quizaccess_seb'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizaccessalias}.allowedbrowserexamkeys")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // Require password.
        $columns[] = (new column(
            'password',
            new lang_string('requirepassword', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.password")
            ->add_callback(function($value) {
                return $value ?? get_string('no');
            });

        // Require network address.
        $columns[] = (new column(
            'subnet',
            new lang_string('requiresubnet', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.subnet")
            ->add_callback(function($value) {
                return $value == "" ? get_string('no') : $value;
            });

        // Enforced delay between 1st and 2nd attempts.
        $columns[] = (new column(
            'delay1st2nd',
            new lang_string('delay1st2nd', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.delay1")
            ->add_callback(function($value, $row) {
                if ($value == 0) {
                    return get_string('notset', 'local_activitysetting');
                } else {
                    return format::format_time($value, $row);
                }
            });

        // Enforced delay between later attempts.
        $columns[] = (new column(
            'delaylaterattempts',
            new lang_string('delaylater', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.delay2")
            ->add_callback(function($value, $row) {
                if ($value == 0) {
                    return get_string('notset', 'local_activitysetting');
                } else {
                    return format::format_time($value, $row);
                }
            });

        // Browser security.
        $columns[] = (new column(
            'browsersecurity',
            new lang_string('browsersecurity', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.browsersecurity")
            ->add_callback(function($value) {
                $options = [
                    '-' => get_string('none', 'quiz'),
                    'securewindow' => get_string('popupwithjavascriptsupport', 'quizaccess_securewindow'),
                ];
                return $options[$value] ?? $value;
            });

        // Completion min attempts.
        $columns[] = (new column(
            'completionminattempts',
            new lang_string('completionminattempts', 'quiz'),
            $this->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_joins($this->get_joins())
            ->add_field("{$quizalias}.completionminattempts")
            ->add_callback(function($value) {
                return $value == 0 ? get_string('notset', 'local_activitysetting') : $value;
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
