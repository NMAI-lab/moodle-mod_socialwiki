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
require('../../config.php');
require($CFG->dirroot . '/mod/socialwiki/locallib.php');
require($CFG->dirroot . '/mod/socialwiki/pagelib.php');

$id    = optional_param('id', 0, PARAM_INT);      // Course Module ID.
$wid   = optional_param('wid', 0, PARAM_INT);     // Wiki ID.
$swid  = optional_param('swid', 0, PARAM_INT);    // Subwiki ID.
$pid   = optional_param('pageid', 0, PARAM_INT);  // Page ID.
$title = optional_param('title', "", PARAM_TEXT); // Page Title.
$group = optional_param('group', 0, PARAM_INT);   // Group ID.

if ($id) {
    /*
     * Case 0:
     *
     * User that comes from a course. Home page must be shown
     *
     * URL params: id -> Course Module ID (required)
     *
     */

    redirect(new moodle_url('/mod/socialwiki/home.php', array('id' => $id)));
} else if ($pid) {
    /*
     * Case 1:
     *
     * A user wants to see a page.
     *
     * URL Params: pageid -> Page ID (required)
     */

    if (!$page = socialwiki_get_page($pid)) {
        print_error('incorrectpageid', 'socialwiki');
    }
    if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
        print_error('incorrectsubwikiid', 'socialwiki');
    }
    if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
        print_error('incorrectwikiid', 'socialwiki');
    }
    if (!$cm = get_coursemodule_from_instance("socialwiki", $subwiki->wikiid)) {
        print_error('invalidcoursemodule', 'socialwiki');
    }

    $group = $subwiki->groupid;

    // Checking course instance.
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    require_login($course, true, $cm);
} else if ($wid && $title) {
    /*
     * Case 2:
     *
     * Trying to read a page from another group or user
     *
     * Page can exists or not.
     *  * If it exists, page must be shown
     *  * If it does not exists, system must ask for its creation
     *
     * URL params: wid -> Subwiki ID (required)
     *             title -> A Page Title (required)
     *             group -> Group ID (optional)
     */

    if (!$wiki = socialwiki_get_wiki($wid)) {
        print_error('incorrectwikiid', 'socialwiki');
    }
    if (!$cm = get_coursemodule_from_instance("socialwiki", $wiki->id)) {
        print_error('invalidcoursemodule', 'socialwiki');
    }

    // Checking course instance.
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    require_login($course, true, $cm);

    $groupmode = groups_get_activity_groupmode($cm);

    $gid = 0;
    if ($groupmode != NOGROUPS) {
        $gid = $group;
    }

    // Getting subwiki instance. If it does not exists, redirect to create page.
    if (!$subwiki = socialwiki_get_subwiki_by_group($wiki->id, $gid)) {
        $context = context_module::instance($cm->id);

        $modeanduser = $wiki->wikimode == 'individual';
        $modeandgroupmember = $wiki->wikimode == 'collaborative' && !groups_is_member($gid);

        $manage = has_capability('mod/socialwiki:managewiki', $context);
        $edit = has_capability('mod/socialwiki:editpage', $context);
        $manageandedit = $manage && $edit;

        if ($groupmode == VISIBLEGROUPS && ($modeanduser || $modeandgroupmember) && !$manageandedit) {
            print_error('nocontent', 'socialwiki');
        }

        redirect(new moodle_url('/mod/socialwiki/create.php', array('wid' => $wiki->id, 'group' => $gid, 'title' => $title)));
    }

    // Checking is there is a page with this title. If it does not exists, redirect to first page.
    if (!$page = socialwiki_get_page_by_title($subwiki->id, $title)) {
        redirect(new moodle_url('/mod/socialwiki/create.php', array('wid' => $wiki->id, 'group' => $gid)));
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

$wikipage = new page_socialwiki_view($wiki, $subwiki, $cm);

$wikipage->set_gid($group);
$wikipage->set_page($page);

$wikipage->print_header();
$wikipage->print_content();
$wikipage->print_footer();
