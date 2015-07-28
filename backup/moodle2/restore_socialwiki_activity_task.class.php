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

/**
 * @package mod_socialwiki
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/socialwiki/backup/moodle2/restore_socialwiki_stepslib.php');

/**
 * wiki restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_socialwiki_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Wiki only has one structure step.
        $this->add_step(new restore_socialwiki_activity_structure_step('socialwiki_structure', 'socialwiki.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('socialwiki', array('intro'), 'socialwiki');
        $contents[] = new restore_decode_content('socialwiki_pages', array('format'), 'socialwiki_page');
        $contents[] = new restore_decode_content('socialwiki_pages', array('content'), 'socialwiki_page');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('WIKIINDEX', '/mod/socialwiki/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('WIKIVIEWBYID', '/mod/socialwiki/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('WIKIPAGEBYID', '/mod/socialwiki/view.php?pageid=$1', 'socialwiki_page');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * wiki logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('socialwiki', 'add', 'view.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('socialwiki', 'update', 'view.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('socialwiki', 'view', 'view.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('socialwiki', 'comments', 'comments.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('socialwiki', 'diff', 'diff.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('socialwiki', 'edit', 'edit.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('socialwiki', 'history', 'history.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('socialwiki', 'map', 'map.php?id={course_module}', '{socialwiki}');
        // TODO: Examine these 2 rules, because module is not "wiki", and it shouldn't happen.
        $rules[] = new restore_log_rule('restore', 'restore', 'view.php?id={course_module}', '{socialwiki}');
        $rules[] = new restore_log_rule('createpage', 'createpage', 'view.php?id={course_module}', '{socialwiki}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('socialwiki', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
