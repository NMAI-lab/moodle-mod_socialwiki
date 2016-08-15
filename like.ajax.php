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
 * Accessed by javascript to change a page being liked/unliked.
 *
 * This will simply act as a toggle turning the like on or off.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
require($CFG->dirroot . '/mod/socialwiki/locallib.php');
require($CFG->dirroot . '/mod/socialwiki/peer.php');

$pageid = required_param('pageid', PARAM_INT);
$navi = optional_param('navi', -2, PARAM_INT);

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
// Checking course instance.
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course, true, $cm);

$out = 'error';
$context = context_module::instance($cm->id);
if ($navi === -2) {
    if (has_capability('mod/socialwiki:editpage', $context) && confirm_sesskey()) {
        $out = socialwiki_page_like($USER->id, $pageid, $subwiki->id);
        $out = "$out " . ($out === 1 ? get_string('like', 'socialwiki') : get_string('likes', 'socialwiki'));
    }
} else {
    if (has_capability('mod/socialwiki:viewpage', $context)) {
        // Set the navigator.
        $SESSION->mod_socialwiki->navi = $navi;
        $out = socialwiki_print_page_content($page, $context, $page->subwikiid);
    }
}
echo json_encode($out);