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
 * This file contains all necessary code to view the navigation tab
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
require($CFG->dirroot . '/mod/socialwiki/table/table.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$tab = optional_param('tabid', 0, PARAM_INT); // Option ID.
// Case 1 User that comes from a course.
if ($id) {
    // Checking course module instance.
    if (!$cm = get_coursemodule_from_id('socialwiki', $id)) {
        print_error('invalidcoursemodule');
    }

    // Checking course instance.
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    require_login($course, true, $cm);

    // Checking socialwiki instance.
    if (!$wiki = socialwiki_get_wiki($cm->instance)) {
        print_error('incorrectwikiid', 'socialwiki');
    }
    $PAGE->set_cm($cm);

    // Getting the subwiki corresponding to that socialwiki, group and user.
    // Getting current group id.
    $currentgroup = groups_get_activity_group($cm);
    $gid = !empty($gid) ? $gid : 0;
    // Set user id 0.
    $userid = 0;

    // Getting subwiki. If it does not exists, redirecting to create page.
    if (!$subwiki = socialwiki_get_subwiki_by_group($wiki->id, $currentgroup, $userid)) {
        $params = array('wid' => $wiki->id, 'group' => $currentgroup, 'uid' => $userid, 'title' => $wiki->firstpagetitle);
        $url = new moodle_url('/mod/socialwiki/create.php', $params);
        redirect($url);
    }
    $context = context_module::instance($cm->id);
    if (!$page = socialwiki_get_first_page($subwiki->id)) {
        // If the front page doesn't exist redirect a teacher to create it.
        if (socialwiki_is_teacher($USER->id, $context)) {
            $params = array('swid' => $subwiki->id, 'title' => $wiki->firstpagetitle);
            $url = new moodle_url('/mod/socialwiki/create.php', $params);
            redirect($url);
        }
    }
} else {
    print_error('incorrectparameters');
}

require_login($course, true, $cm);
require_capability('mod/socialwiki:viewpage', $context);

$wikipage = new page_socialwiki_home($wiki, $subwiki, $cm);

$wikipage->set_tab($tab);
$wikipage->print_header();
$wikipage->print_content();
$wikipage->print_footer();
