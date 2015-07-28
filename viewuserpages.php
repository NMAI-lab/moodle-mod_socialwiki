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
 * View a user profile.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require($CFG->dirroot . '/mod/socialwiki/pagelib.php');
require($CFG->dirroot . '/mod/socialwiki/locallib.php');
require($CFG->dirroot . '/mod/socialwiki/table/table.php');

$subwikiid = required_param('subwikiid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

if (!$subwiki = socialwiki_get_subwiki($subwikiid)) {
    print_error('incorrectsubwikiid', 'socialwiki');
}
if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'socialwiki');
}

if (!$cm = get_coursemodule_from_instance('socialwiki', $wiki->id)) {
    print_error('invalidcoursemodule');
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

$viewuserpage = new page_socialwiki_viewuserpages($wiki, $subwiki, $cm, $userid);

$viewuserpage->print_header();
$viewuserpage->print_content();
$viewuserpage->print_footer();

