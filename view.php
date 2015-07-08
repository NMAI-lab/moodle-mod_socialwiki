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
 * This file contains all necessary code to view a socialwiki page
 *
 * @package mod_socialwiki
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
require('../../config.php');
require($CFG->dirroot . '/mod/socialwiki/locallib.php');
require($CFG->dirroot . '/mod/socialwiki/pagelib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.

$pageid = optional_param('pageid', 0, PARAM_INT); // Page ID.

$wid = optional_param('wid', 0, PARAM_INT); // Wiki ID.
$title = optional_param('title', '', PARAM_TEXT); // Page Title.
$currentgroup = optional_param('group', 0, PARAM_INT); // Group ID.
$userid = optional_param('uid', 0, PARAM_INT); // User ID.
$groupanduser = optional_param('groupanduser', 0, PARAM_TEXT);

$edit = optional_param('edit', -1, PARAM_BOOL);

$action = optional_param('action', '', PARAM_ALPHA);
$swid = optional_param('swid', 0, PARAM_INT); // Subwiki ID.

/*
 * Case 0:
 *
 * User that comes from a course. Home page must be shown
 *
 * URL params: id -> course module id
 *
 */
if ($id) {
    $url = new moodle_url('/mod/socialwiki/home.php', array('id' => $id));
    redirect($url);

    /*
     * Case 1:
     *
     * A user wants to see a page.
     *
     * URL Params: pageid -> page id
     *
     */
} else if ($pageid) {

    // Checking page instance.
    if (!$page = socialwiki_get_page($pageid)) {
        print_error('incorrectpageid', 'socialwiki');
    }

    // Checking subwiki.
    if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
        print_error('incorrectsubwikiid', 'socialwiki');
    }

    // Checking socialwiki instance of that subwiki.
    if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
        print_error('incorrectwikiid', 'socialwiki');
    }

    // Checking course module instance.
    if (!$cm = get_coursemodule_from_instance("socialwiki", $subwiki->wikiid)) {
        print_error('invalidcoursemodule');
    }

    $currentgroup = $subwiki->groupid;

    // Checking course instance.
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    require_login($course, true, $cm);
    /*
     * Case 2:
     *
     * Trying to read a page from another group or user
     *
     * Page can exists or not.
     *  * If it exists, page must be shown
     *  * If it does not exists, system must ask for its creation
     *
     * URL params: wid -> subwiki id (required)
     *             title -> a page title (required)
     *             group -> group id (optional)
     *             uid -> user id (optional)
     *             groupanduser -> (optional)
     */
} else if ($wid && $title) {

    // Setting wiki instance.
    if (!$wiki = socialwiki_get_wiki($wid)) {
        print_error('incorrectwikiid', 'socialwiki');
    }

    // Checking course module.
    if (!$cm = get_coursemodule_from_instance("socialwiki", $wiki->id)) {
        print_error('invalidcoursemodule');
    }

    // Checking course instance.
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    require_login($course, true, $cm);

    $groupmode = groups_get_activity_groupmode($cm);

    if ($groupmode == NOGROUPS) {
        $gid = 0;
        $uid = 0;
    } else {
        $gid = $currentgroup;
        $uid = 0;
    }

    // Getting subwiki instance. If it does not exists, redirect to create page.
    if (!$subwiki = socialwiki_get_subwiki_by_group($wiki->id, $gid, $uid)) {
        $context = context_module::instance($cm->id);

        $modeanduser = $wiki->wikimode == 'individual' && $uid != $USER->id;
        $modeandgroupmember = $wiki->wikimode == 'collaborative' && !groups_is_member($gid);

        $manage = has_capability('mod/socialwiki:managewiki', $context);
        $edit = has_capability('mod/socialwiki:editpage', $context);
        $manageandedit = $manage && $edit;

        if ($groupmode == VISIBLEGROUPS and ( $modeanduser || $modeandgroupmember) and ! $manageandedit) {
            print_error('nocontent', 'socialwiki');
        }

        $params = array('wid' => $wiki->id, 'group' => $gid, 'uid' => $uid, 'title' => $title);
        $url = new moodle_url('/mod/socialwiki/create.php', $params);
        redirect($url);
    }

    // Checking is there is a page with this title. If it does not exists, redirect to first page.
    if (!$page = socialwiki_get_page_by_title($subwiki->id, $title)) {
        $params = array('wid' => $wiki->id, 'group' => $gid, 'uid' => $uid, 'title' => $wiki->firstpagetitle);
        // Check to see if the first page has been created.
        if (!socialwiki_get_page_by_title($subwiki->id, $wiki->firstpagetitle)) {
            $url = new moodle_url('/mod/socialwiki/create.php', $params);
        } else {
            $url = new moodle_url('/mod/socialwiki/view.php', $params);
        }
        redirect($url);
    }
} else {
    print_error('incorrectparameters');
}

$context = context_module::instance($cm->id);
require_capability('mod/socialwiki:viewpage', $context);

// Update 'viewed' state if required by completion system.
require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

if (($edit != - 1) and $PAGE->user_allowed_editing()) {
    $USER->editing = $edit;
}

$wikipage = new page_socialwiki_view($wiki, $subwiki, $cm);

/* The following piece of code is used in order
 * to perform set_url correctly. It is necessary in order
 * to make page_socialwiki_view class know that this page
 * has been called via its id.
 */
if ($id) {
    $wikipage->set_coursemodule($id);
}

$wikipage->set_gid($currentgroup);
$wikipage->set_page($page);

if ($pageid) {
    add_to_log($course->id, 'socialwiki', 'view', "view.php?pageid=$pageid", $pageid, $cm->id);
} else if ($id) {
    add_to_log($course->id, 'socialwiki', 'view', "view.php?id=$id", $id, $cm->id);
} else if ($wid && $title) {
    add_to_log($course->id, 'socialwiki', 'view', "view.php?wid=$wid&title=$title", $wid, $cm->id);
}

$wikipage->print_header();

$wikipage->print_content();

$wikipage->print_footer();
