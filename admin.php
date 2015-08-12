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
 * Delete or edit pages.
 *
 * This will show options for deleting or editing any pages in the current subwiki.
 * The user must have wiki:managewiki ability to show options.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require($CFG->dirroot . '/mod/socialwiki/locallib.php');
require($CFG->dirroot . '/mod/socialwiki/pagelib.php');

$pageid  = required_param('pageid', PARAM_INT);     // Page ID.
$delete  = optional_param('delete', 0, PARAM_INT);  // Page ID to be deleted.
$listall = optional_param('listall', 0, PARAM_INT); // List all pages.

if (!$page = socialwiki_get_page($pageid)) {
    print_error('incorrectpageid', 'socialwiki');
}
if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
    print_error('incorrectsubwikiid', 'socialwiki');
}
if (!$cm = get_coursemodule_from_instance("socialwiki", $subwiki->wikiid)) {
    print_error('invalidcoursemodule', 'socialwiki');
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'socialwiki');
}

require_login($course, true, $cm);
require_capability('mod/socialwiki:managewiki', context_module::instance($cm->id));

// Delete page if a page ID to delete was supplied.
if (!empty($delete) && confirm_sesskey()) {
    socialwiki_delete_pages($context, $delete, $page->subwikiid);
    // When current wiki page is deleted, then redirect user to create that page, as current pageid is invalid after deletion.
    if ($pageid == $delete) {
        redirect(new moodle_url('/mod/socialwiki/home.php', array('id' => $cm->id)));
    }
}

$wikipage = new page_socialwiki_admin($wiki, $subwiki, $cm);
$wikipage->set_page($page);

$wikipage->print_header();
$wikipage->set_view($listall);
$wikipage->print_content();
$wikipage->print_footer();
