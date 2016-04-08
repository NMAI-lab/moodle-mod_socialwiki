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
 * This is run when the follow button is clicked.
 *
 * This will simply act as a toggle turning the follow on or off.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
require_once($CFG->dirroot . '/mod/socialwiki/peer.php');

$from   = required_param('from', PARAM_TEXT);      // The url of the previous page.
$pageid = optional_param('pageid', -1, PARAM_INT); // Get the author from a page.
$user2  = optional_param('user2', -1, PARAM_INT);  // The user that is going to be followed.
$swid   = optional_param('swid', -1, PARAM_INT);   // Subwiki ID.

if (!confirm_sesskey()) {
    print_error(get_string('invalidsesskey', 'socialwiki'));
}

if ($swid != -1) {
    $subwiki = socialwiki_get_subwiki($swid);
}

if ($pageid > -1) {
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
    require_capability('mod/socialwiki:editpage', context_module::instance($cm->id));

    // Get the author of the current page.
    $page = socialwiki_get_page($pageid);
    $user2 = $page->userid;
    // Check if the user is following themselves.
    if ($USER->id == $user2) {
        // Display error with a link back to the page they came from.
        $PAGE->set_context($context);
        $PAGE->set_cm($cm);
        $PAGE->set_url('/mod/socialwiki/follow.php');
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox', 'socialwiki_followerror');
        echo '<p>' . get_string("cannotfollow", 'socialwiki') . '</p>' . '<br/>';
        echo html_writer::link($from, 'Go back');
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
    } else {
        // Check if the use is already following the author.
        if (socialwiki_is_following($USER->id, $user2, $subwiki->id)) {
            // Delete the record if the user is already following the author.
            socialwiki_unfollow($USER->id, $user2, $subwiki->id);
            redirect($from);
        } else {
            // If the user isn't following the author add a new follow.
            $record = new StdClass();
            $record->userfromid = $USER->id;
            $record->usertoid = $user2;
            $record->subwikiid = $subwiki->id;
            $DB->insert_record('socialwiki_follows', $record);
            // Go back to the page you came from.
            redirect($from);
        }
    }
} else if ($user2 != -1) {
    // Check if the use is already following the author.
    if (socialwiki_is_following($USER->id, $user2, $subwiki->id)) {
        // Delete the record if the user is already following the author.
        socialwiki_unfollow($USER->id, $user2, $subwiki->id);
    } else {
        // If the user isn't following the author add a new follow.
        $record = new StdClass();
        $record->userfromid = $USER->id;
        $record->usertoid = $user2;
        $record->subwikiid = $subwiki->id;
        $DB->insert_record('socialwiki_follows', $record);
    }
    socialwiki_peer::socialwiki_update_peers(false, true, $swid, $USER->id); // Update peer info in session vars.
    // Go back to the page you came from.
    redirect($from);
} else {
    print_error('nouser', 'socialwiki');
}