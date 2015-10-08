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

$id  = required_param('id', PARAM_INT);    // Course module ID.
$tab = optional_param('tabid', 0, PARAM_INT); // Tab Option ID.

// Checking course module instance.
if (!$cm = get_coursemodule_from_id('socialwiki', $id)) {
    print_error('invalidcoursemodule', 'socialwiki');
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
// Getting current group ID.
$currentgroup = groups_get_activity_group($cm);
$gid = !empty($gid) ? $gid : 0;
// Set user ID to 0.
$userid = 0;

$context = context_module::instance($cm->id);
// Getting subwiki. If it does not exists, redirecting to create page.
if (!$subwiki = socialwiki_get_subwiki_by_group($wiki->id, $currentgroup, $userid)) {
    socialwiki_add_subwiki($wiki->id, $currentgroup, $userid);
    $subwiki = socialwiki_get_subwiki_by_group($wiki->id, $currentgroup, $userid);
}

require_login($course, true, $cm);
require_capability('mod/socialwiki:viewpage', $context);

$wikipage = new page_socialwiki_home($wiki, $subwiki, $cm);

$wikipage->set_title(get_string('hometitle', 'socialwiki'));
$wikipage->set_tab($tab);
$wikipage->print_header();
$wikipage->print_content();
$wikipage->print_footer();
