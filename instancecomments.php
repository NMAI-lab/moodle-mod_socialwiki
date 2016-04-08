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
 * Page to show comments.
 *
 * @package   mod_socialwiki
 * @copyright 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyright 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Jordi Piguillem
 * @author Marc Alier
 * @author David Jimenez
 * @author Josep Arus
 * @author Daniel Serrano
 * @author Kenneth Riba
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . "/mod/socialwiki/locallib.php");
require_once($CFG->dirroot . "/mod/socialwiki/pagelib.php");
require_once($CFG->dirroot . '/mod/socialwiki/comments_form.php');

$pageid     = required_param('pageid', PARAM_TEXT);
$action     = optional_param('action', "", PARAM_ALPHANUMEXT);
$id         = optional_param('id', 0, PARAM_INT);
$commentid  = optional_param('commentid', 0, PARAM_INT);
$newcontent = optional_param('newcontent', "", PARAM_CLEANHTML);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);

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

// Create correct page.
if ($action == 'delete' && !$confirm) {
    // Confirm the use would like to delete the comment.
    $comm = new page_socialwiki_deletecomment($wiki, $subwiki, $cm);
} else {
    // Check sesskey for modifications.
    if (!confirm_sesskey()) {
        print_error(get_string('invalidsesskey', 'socialwiki'));
    }
    // Handle add, edit, delete.
    $comm = new page_socialwiki_handlecomment($wiki, $subwiki, $cm);
}
$comm->set_page($page);

// Set correct action.
if ($action == 'delete') {
    // Hand over commentid.
    $comm->set_action($action, $commentid);
} else {
    if (empty($newcontent)) {
        $form = new mod_socialwiki_comments_form();
        $newcomment = $form->get_data();
        $content = $newcomment->entrycomment_editor['text'];
    } else {
        $content = $newcontent;
    }

    if ($action == 'edit') {
        $comm->set_action($action, $id, $content);
    } else {
        $action = 'add';
        $comm->set_action($action, 0, $content);
    }
}

$comm->print_header();
$comm->print_content();
$comm->print_footer();
