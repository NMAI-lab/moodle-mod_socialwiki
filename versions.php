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
 * The versions page.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require($CFG->dirroot . "/mod/socialwiki/locallib.php");
require($CFG->dirroot . '/mod/socialwiki/pagelib.php');
require($CFG->dirroot . "/mod/socialwiki/peer.php");

$pageid = required_param('pageid', PARAM_TEXT); // Page ID.
$view   = optional_param('view', 0, PARAM_INT); // Option ID.

if (!$page = socialwiki_get_page($pageid)) {
    print_error('incorrectpageid', 'socialwiki');
}

if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
    print_error('incorrectsubwikiid', 'socialwiki');
}

if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'socialwiki');
}

if (!$cm = get_coursemodule_from_instance('socialwiki', $wiki->id)) {
    print_error('invalidcoursemodule', 'socialwiki');
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/socialwiki:viewpage', context_module::instance($cm->id));

// Print the page header.
$wikipage = new page_socialwiki_versions($wiki, $subwiki, $cm, $view);

$wikipage->set_page($page);

$wikipage->print_header();
$wikipage->print_content();
$wikipage->print_footer();
