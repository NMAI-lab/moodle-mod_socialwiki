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
 * @package mod-wiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/lib.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
require_once($CFG->dirroot . '/mod/socialwiki/pagelib.php');
require_once($CFG->dirroot . '/mod/socialwiki/socialwikitree.php');


$search = optional_param('searchstring', null, PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$searchcontent = optional_param('searchsocialwiki_socialwiki_socialwiki_wikicontent', 1, PARAM_INT); //?
$cmid = optional_param('cmid', 0, PARAM_INT);
$pageid = optional_param('pageid', -1, PARAM_INT);
$option = optional_param('option', 0, PARAM_INT); // Option ID
$exact = optional_param('exact', 0, PARAM_INT); // if match should be exact (wikilinks)

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    echo $courseid;
    print_error('invalidcourseid');
}
if (!$cm = get_coursemodule_from_id('socialwiki', $cmid)) {
    print_error('invalidcoursemodule');
}

require_login($course, true, $cm);

// @TODO: Fix call to wiki_get_subwiki_by_group
if (!$gid = groups_get_activity_group($cm)) {
    $gid = 0;
}
if (!$subwiki = socialwiki_get_subwiki_by_group($cm->instance, $gid)) {
    return false;
}
if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'socialwiki');
}

$wikipage = new page_socialwiki_search($wiki, $subwiki, $cm);

//make * a wild-card search
if ($search == "*")
    $search = "";

if ($exact != 0) { //exact match on page title
    $wikipage->set_search_string($search, $searchcontent, true);
} else {
    $wikipage->set_search_string($search, $searchcontent, false);
}

$wikipage->set_title(get_string('searchresultsfor', 'socialwiki') . ": " . $search);

//$page = socialwiki_get_page($pageid);
//if ($pageid != -1)
//{
//	$wikipage->set_page($page);
//}
$wikipage->set_view($option);

$wikipage->print_header();

$wikipage->print_content();

$wikipage->print_footer();
