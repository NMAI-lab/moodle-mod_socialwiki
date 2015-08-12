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
 * This file contains all necessary code to edit a wiki page
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
require($CFG->dirroot . '/mod/socialwiki/edit_form.php');

$pageid  = required_param('pageid', PARAM_INT);              // Page ID.
$format  = optional_param('contentformat', "", PARAM_ALPHA); // Content Format (eg. creole, HTML).
$makenew = optional_param('makenew', 0, PARAM_INT);          // 0 is new versions and 1 is whole new page (first version).
$option  = optional_param('editoption', "", PARAM_TEXT);     // Button option that was clicked.
$section = optional_param('section', "", PARAM_TEXT);

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
if (!empty($section) && !$sectioncontent = socialwiki_get_section_page($page, $section)) {
    print_error('invalidsection', 'socialwiki');
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course, true, $cm);
require_capability('mod/socialwiki:editpage', context_module::instance($cm->id));

if ($option == get_string('save', 'socialwiki')) {
    if (!confirm_sesskey()) {
        print_error(get_string('invalidsesskey', 'socialwiki'));
    }
    if ($makenew == 0) {
        $newpageid = socialwiki_create_page($subwiki->id, $page->title, $format, $USER->id, $page->id);
        $newpage = socialwiki_get_page($newpageid);

        $wikipage = new page_socialwiki_save($wiki, $subwiki, $cm, $makenew);
        $wikipage->set_page($newpage);

        socialwiki_increment_pageviews($newpage);
        socialwiki_increment_user_views($USER->id, $newpage->id);

        socialwiki_add_like($USER->id, $newpageid, $subwiki->id);
    } else {
        $wikipage = new page_socialwiki_save($wiki, $subwiki, $cm, $makenew);
        $wikipage->set_page($page);

        socialwiki_increment_pageviews($page);
        socialwiki_increment_user_views($USER->id, $page->id);
    }

    $wikipage->set_newcontent("");
    $wikipage->set_upload(true);
} else {
    if ($option == get_string('preview')) {
        if (!confirm_sesskey()) {
            print_error(get_string('invalidsesskey', 'socialwiki'));
        }
        $wikipage = new page_socialwiki_preview($wiki, $subwiki, $cm);
        $wikipage->set_page($page);
    } else {
        if ($option == get_string('cancel')) {
            redirect($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $pageid);
        } else {
            $wikipage = new page_socialwiki_edit($wiki, $subwiki, $cm, $makenew);
            $wikipage->set_page($page);
            $wikipage->set_upload($option == get_string('upload', 'socialwiki'));
        }
    }
}

if (!empty($section)) {
    $wikipage->set_section($sectioncontent, $section);
}
if (!empty($format)) {
    $wikipage->set_format($format);
}

$wikipage->print_header();
$wikipage->print_content();
$wikipage->print_footer();

