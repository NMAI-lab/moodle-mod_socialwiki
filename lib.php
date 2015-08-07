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
 * Library of functions and constants for module wiki
 *
 * It contains the great majority of functions defined by Moodle
 * that are mandatory to develop a module.
 *
 * @package   mod_socialwiki
 * @copyright 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyright 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Jordi Piguillem
 * @author Marc Alier
 * @author David Jimenez
 * @author Josep Arus
 * @author Kenneth Riba
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $wiki The wiki object.
 * @return int The id of the newly inserted wiki record
 * */
function socialwiki_add_instance($wiki) {
    global $DB;

    $wiki->timemodified = time();
    // May have to add extra stuff in here.
    if (empty($wiki->forceformat)) {
        $wiki->forceformat = 0;
    }
    return $DB->insert_record('socialwiki', $wiki);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $wiki The wiki object.
 * @return bool
 * */
function socialwiki_update_instance($wiki) {
    global $DB;

    $wiki->timemodified = time();
    $wiki->id = $wiki->instance;
    if (empty($wiki->forceformat)) {
        $wiki->forceformat = 0;
    }
    // May have to add extra stuff in here.
    return $DB->update_record('socialwiki', $wiki);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id ID of the module instance.
 * @return bool
 */
function socialwiki_delete_instance($id) {
    global $DB;

    if (!$wiki = $DB->get_record('socialwiki', array('id' => $id))) {
        return false;
    }

    $result = true;

    // Get subwiki information.
    $subwikis = $DB->get_records('socialwiki_subwikis', array('wikiid' => $wiki->id));

    foreach ($subwikis as $subwiki) {
        // Get likes and delete them.
        if (!$DB->delete_records('socialwiki_likes', array('subwikiid' => $subwiki->id), IGNORE_MISSING)) {
            $result = false;
        }

        // Get follows and delete them.
        if (!$DB->delete_records('socialwiki_follows', array('subwikiid' => $subwiki->id), IGNORE_MISSING)) {
            $result = false;
        }

        // Delete pages.
        if (!$DB->delete_records('socialwiki_pages', array('subwikiid' => $subwiki->id), IGNORE_MISSING)) {
            $result = false;
        }

        // Delete any subwikis.
        if (!$DB->delete_records('socialwiki_subwikis', array('id' => $subwiki->id), IGNORE_MISSING)) {
            $result = false;
        }
    }

    // Delete any dependent records here.
    if (!$DB->delete_records('socialwiki', array('id' => $wiki->id))) {
        $result = false;
    }

    return $result;
}

function socialwiki_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/socialwiki/pagelib.php');
    require_once($CFG->dirroot . '/tag/lib.php');

    $componentstr = get_string('modulenameplural', 'socialwiki');
    $status = array();

    // Get the wiki(s) in this course.
    if (!$wikis = $DB->get_records('socialwiki', array('course' => $data->courseid))) {
        return false;
    }
    $errors = false;
    foreach ($wikis as $wiki) {

        // Remove all comments.
        if (!empty($data->reset_socialwiki_comments)) {
            if (!$cm = get_coursemodule_from_instance('socialwiki', $wiki->id)) {
                continue;
            }
            $context = context_module::instance($cm->id);
            $DB->delete_records_select('comments', "contextid = ? AND commentarea='socialwiki_page'", array($context->id));
            $status[] = array('component' => $componentstr, 'item' => get_string('deleteallcomments'), 'error' => false);
        }

        if (!empty($data->reset_wiki_tags)) {
            // Get subwiki information.
            $subwikis = $DB->get_records('socialwiki_subwikis', array('wikiid' => $wiki->id));

            foreach ($subwikis as $subwiki) {
                if ($pages = $DB->get_records('socialwiki_pages', array('subwikiid' => $subwiki->id))) {
                    foreach ($pages as $page) {
                        $tags = tag_get_tags_array('socialwiki_pages', $page->id);
                        foreach ($tags as $tagid => $tagname) {
                            // Delete the related tag_instances related to the wiki page.
                            $errors = tag_delete_instance('socialwiki_pages', $page->id, $tagid);
                            $status[] = array('component' => $componentstr,
                                'item' => get_string('tagsdeleted', 'socialwiki'), 'error' => $errors);
                        }
                    }
                }
            }
        }
    }
    return $status;
}

function socialwiki_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'socialwikiheader', get_string('modulenameplural', 'socialwiki'));
    $mform->addElement('advcheckbox', 'reset_socialwiki_tags', get_string('removeallwikitags', 'socialwiki'));
    $mform->addElement('advcheckbox', 'reset_socialwiki_comments', get_string('deleteallcomments'));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 */
function socialwiki_user_outline() {
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return bool
 */
function socialwiki_user_complete() {
    return true;
}

/**
 * Indicates API features that the wiki supports.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function socialwiki_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_RATE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in wiki activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @uses CONTEXT_MODULE
 * @uses VISIBLEGROUPS
 * @param stdClass $course
 * @param bool $viewfullnames capability
 * @param int $timestart
 * @return bool
 * */
function socialwiki_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $DB, $OUTPUT;

    $sql = "SELECT p.*, w.id as wikiid, sw.groupid
            FROM {socialwiki_pages} p
            JOIN {socialwiki_subwikis} sw ON sw.id = p.subwikiid
            JOIN {socialwiki} w ON w.id = sw.wikiid
            WHERE p.timecreated > ? AND w.course = ?
            ORDER BY p.timecreated ASC";
    if (!$pages = $DB->get_records_sql($sql, array($timestart, $course->id))) {
        return false;
    }

    $modinfo = get_fast_modinfo($course);
    $wikis = array();

    foreach ($pages as $page) {
        if (!isset($modinfo->instances['socialwiki'][$page->wikiid])) {
            // Not visible.
            continue;
        }
        $cm = $modinfo->instances['socialwiki'][$page->wikiid];
        if (!$cm->uservisible) {
            continue;
        }
        $context = context_module::instance($cm->id);

        if (!has_capability('mod/socialwiki:viewpage', $context)) {
            continue;
        }

        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($groupmode) {
            if ($groupmode == SEPARATEGROUPS && !has_capability('mod/socialwiki:managewiki', $context)) {
                // Separate mode.
                if (isguestuser()) {
                    // Shortcut.
                    continue;
                }

                if (is_null($modinfo->groups)) {
                    $modinfo->groups = groups_get_user_groups($course->id); // Load all my groups and cache it in modinfo.
                }

                if (!in_array($page->groupid, $modinfo->groups[0])) {
                    continue;
                }
            }
        }
        $wikis[] = $page;
    }
    unset($pages);

    if (!$wikis) {
        return false;
    }
    echo $OUTPUT->heading(get_string("updatedwikipages", 'socialwiki') . ':', 3);
    foreach ($wikis as $wiki) {
        $cm = $modinfo->instances['socialwiki'][$wiki->wikiid];
        $link = $CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $wiki->id;
        print_recent_activity_note($wiki->timecreated, $wiki, $cm->name, $link, false, $viewfullnames);
    }

    return true; // True if anything was printed, otherwise false.
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return bool
 */
function socialwiki_cron() {
    return true;
}

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $wikiid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 */
function socialwiki_grades($wikiid) {
    return null;
}

/**
 * File serving callback.
 *
 * @copyright Josep Arus
 * @package  mod_socialwiki
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file was not found, just send the file otherwise and do not return anything
 */
function socialwiki_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    require_once($CFG->dirroot . "/mod/socialwiki/locallib.php");

    if ($filearea == 'attachments') {
        $swid = (int) array_shift($args);

        if (!$subwiki = socialwiki_get_subwiki($swid)) {
            return false;
        }

        require_capability('mod/socialwiki:viewpage', $context);

        $relativepath = implode('/', $args);

        $fullpath = "/$context->id/mod_socialwiki/attachments/$swid/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;

        send_stored_file($file, $lifetime, 0, $options);
    }
}

function socialwiki_search_form($cm, $search = "") {
    global $CFG;

    $output = '<div class="socialwikisearch">';
    $output .= '<form method="post" action="' . $CFG->wwwroot . '/mod/socialwiki/search.php" style="display:inline">';
    $output .= '<fieldset class="invisiblefieldset">';
    $output .= '<legend class="accesshide">' . get_string('search', 'socialwiki') . '</legend>';
    $output .= '<label class="accesshide" for="search_socialwiki">' . get_string("searchterms", "socialwiki") . '</label>';
    $output .= '<input id="search_socialwiki" name="searchstring" type="text" size="18" value="';
    $output .= s($search, true) . '" alt="search" />';
    $output .= '<input name="courseid" type="hidden" value="' . $cm->course . '" />';
    $output .= '<input name="cmid" type="hidden" value="' . $cm->id . '" />';
    $output .= '<input name="search_social_content" type="hidden" value="1" />';
    $output .= '<input value="' . get_string('search', 'socialwiki') . '" type="submit" />';
    $output .= '</fieldset>';
    $output .= '</form>';
    $output .= '</div>';

    return $output;
}

/**
 * Returns all other caps used in wiki module
 *
 * @return array
 */
function socialwiki_get_extra_capabilities() {
    return array('moodle/comment:view', 'moodle/comment:post', 'moodle/comment:delete');
}

/**
 * Running addtional permission check on plugin, for example, plugins
 * may have switch to turn on/off comments option, this callback will
 * affect UI display, not like pluginname_comment_validate only throw
 * exceptions.
 * Capability check has been done in comment->check_permissions(), we
 * don't need to do it again here.
 *
 * @package  mod_socialwiki
 * @category comment
 * @return array
 */
function socialwiki_comment_permissions() {
    return array('post' => true, 'view' => true);
}

/**
 * Validate comment parameter before perform other comments actions.
 *
 * @param stdClass $commentparam {
 *              context     => context the context object
 *              courseid    => int course ID
 *              cm          => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int item ID
 * }
 *
 * @package  mod_socialwiki
 * @category comment
 *
 * @return bool
 */
function socialwiki_comment_validate($commentparam) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
    // Validate comment area.
    if ($commentparam->commentarea != 'socialwiki_page') {
        throw new comment_exception('invalidcommentarea');
    }
    // Validate itemid.
    if (!$record = $DB->get_record('socialwiki_pages', array('id' => $commentparam->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$subwiki = socialwiki_get_subwiki($record->subwikiid)) {
        throw new comment_exception('invalidsubwikiid');
    }
    if (!$wiki = socialwiki_get_wiki_from_pageid($commentparam->itemid)) {
        throw new comment_exception('invalidid', 'data');
    }
    if (!$course = $DB->get_record('course', array('id' => $wiki->course))) {
        throw new comment_exception('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance('socialwiki', $wiki->id, $course->id)) {
        throw new comment_exception('invalidcoursemodule');
    }
    $context = context_module::instance($cm->id);
    // Group access.
    if ($subwiki->groupid) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
            if (!groups_is_member($subwiki->groupid)) {
                throw new comment_exception('notmemberofgroup');
            }
        }
    }
    // Validate context ID.
    if ($context->id != $commentparam->context->id) {
        throw new comment_exception('invalidcontext');
    }
    // Validation for comment deletion.
    if (!empty($commentparam->commentid)) {
        if ($comment = $DB->get_record('comments', array('id' => $commentparam->commentid))) {
            if ($comment->commentarea != 'socialwiki_page') {
                throw new comment_exception('invalidcommentarea');
            }
            if ($comment->contextid != $context->id) {
                throw new comment_exception('invalidcontext');
            }
            if ($comment->itemid != $commentparam->itemid) {
                throw new comment_exception('invalidcommentitemid');
            }
        } else {
            throw new comment_exception('invalidcommentid');
        }
    }
    return true;
}

/**
 * Return a list of page types.
 *
 * @param string $pagetype Current page type.
 * @param stdClass $parentcontext Block's parent context.
 * @param stdClass $currentcontext Current context of block.
 */
function socialwiki_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array(
        'mod-socialwiki-*' => get_string('page-mod-socialwiki-x', 'socialwiki'),
        'mod-socialwiki-view' => get_string('page-mod-socialwiki-view', 'socialwiki'),
        'mod-socialwiki-comments' => get_string('page-mod-socialwiki-comments', 'socialwiki'),
        'mod-socialwiki-versions' => get_string('page-mod-socialwiki-versions', 'socialwiki')
    );
    return $modulepagetype;
}
