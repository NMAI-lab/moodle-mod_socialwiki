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

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
require_once($CFG->dirroot . '/mod/socialwiki/peer.php');

$pageid = required_param('pageid', PARAM_INT);
$refresh = optional_param('refresh', 0, PARAM_RAW); // 1 without javascript.

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
    print_error('invalidcoursemodule');
}
$context = context_module::instance($cm->id);
if (!is_enrolled($context, $USER->id)) {
    // Must be an enrolled user to like a page.
    print_error('connotlike', 'socialwiki');
}

if (socialwiki_liked($USER->id, $pageid)) {
    socialwiki_delete_like($USER->id, $pageid);
} else {
    socialwiki_add_like($USER->id, $pageid, $subwiki->id);

    // TODO: could optimize which peers we recompute: only those who have likes in common.
}
peer::socialwiki_update_peers(true, false, $subwiki->id, $USER->id); // Update like similarity to other peers.

// Refresh without javascript otherwise send back likes.
if ($refresh) {
    redirect($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $pageid);
} else {
    echo socialwiki_numlikes($pageid);
}
