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
 * This contains functions and classes that will be used by scripts in wiki module
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

require($CFG->dirroot . '/mod/socialwiki/lib.php');
require($CFG->dirroot . '/mod/socialwiki/parser/parser.php');
require($CFG->dirroot . '/tag/lib.php');

define('SOCIALFORMAT_CREOLE', '37');
define('SOCIALFORMAT_NWIKI', '38');
define('SOCIAL_NO_VALID_RATE', '-999');
define('SOCIALIMPROVEMENT', '+');
define('SOCIALEQUAL', '=');
define('SOCIALWORST', '-');

/**
 * Get a wiki instance.
 *
 * @param int $wid The wiki ID.
 * @return stdClass
 */
function socialwiki_get_wiki($wid) {
    global $DB;
    return $DB->get_record('socialwiki', array('id' => $wid));
}

/**
 * Get sub wiki instances with same wiki id.
 *
 * @param int $wid The wiki ID.
 * @return stdClass
 */
function socialwiki_get_subwikis($wid) {
    global $DB;
    return $DB->get_records('socialwiki_subwikis', array('wikiid' => $wid));
}

/**
 * Get a sub wiki instance by wiki id and group id.
 *
 * @param int $wid The wiki ID.
 * @param int $gid The group ID.
 * @param int $uid The user ID.
 * @return stdClass
 */
function socialwiki_get_subwiki_by_group($wid, $gid, $uid = 0) {
    global $DB;
    return $DB->get_record('socialwiki_subwikis', array('wikiid' => $wid, 'groupid' => $gid, 'userid' => $uid));
}

/**
 * Get a sub wiki instace by instance id.
 *
 * @param int $swid The subwiki ID.
 * @return stdClass
 */
function socialwiki_get_subwiki($swid) {
    global $DB;
    return $DB->get_record('socialwiki_subwikis', array('id' => $swid));
}

/**
 * Add a new sub wiki instance.
 *
 * @param int $wid The wiki ID.
 * @param int $gid The group ID.
 * @param int $uid The first user ID.
 * @return int
 */
function socialwiki_add_subwiki($wid, $gid, $uid = 0) {
    global $DB;

    $record = new StdClass();
    $record->wikiid = $wid;
    $record->groupid = $gid;
    $record->userid = $uid;

    $insertid = $DB->insert_record('socialwiki_subwikis', $record);
    return $insertid;
}

/**
 * Get a wiki instance by page ID.
 *
 * @param int $pid The page ID.
 * @return stdClass
 */
function socialwiki_get_wiki_from_pageid($pid) {
    global $DB;

    $sql = "SELECT w.* FROM {socialwiki} w, {socialwiki_subwikis} s, {socialwiki_pages} p
            WHERE p.id = ? AND p.subwikiid = s.id AND s.wikiid = w.id";

    return $DB->get_record_sql($sql, array($pid));
}

/**
 * Get a wiki page by page ID.
 *
 * @param int $pid The page ID.
 * @return stdClass
 */
function socialwiki_get_page($pid) {
    global $DB;
    return $DB->get_record('socialwiki_pages', array('id' => $pid));
}

/**
 * Get all pages for a user.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_pages_from_userid($uid, $swid) {
    Global $DB;
    $select = 'userid=? And subwikiid=?';
    return $DB->get_records_select('socialwiki_pages', $select, array($uid, $swid));
}

/**
 * Get page section.
 *
 * @param stdClass $page
 * @param string $section
 */
function socialwiki_get_section_page($page, $section) {
    return socialwiki_parser_proxy::get_section($page->content, $page->format, $section);
}

/**
 * Get a wiki page by page title.
 *
 * @param int $swid The subwiki ID.
 * @param string $title The page title.
 * @return stdClass
 */
function socialwiki_get_page_by_title($swid, $title) {
    global $DB, $USER;
    $records = $DB->get_records('socialwiki_pages', array('subwikiid' => $swid, 'title' => $title));
    if (count($records) > 0) {

        foreach ($records as $r) {
            if (socialwiki_is_user_favourite($USER->id, $r->id, $swid)) {
                return $r;
            }
        }
        // The user has no fave.
        return $records[max(array_keys($records))];
    } else {
        return $records;
    }
}

/**
 * Get first page of wiki instace.
 *
 * @param int $swid The subwiki ID.
 * @param int $module Wiki instance object.
 * @return stdClass Last version of first page edited by a teacher
 */
function socialwiki_get_first_page($swid, $module = null) {
    global $DB, $COURSE;
    $context = context_course::instance($COURSE->id);
    $teachers = socialwiki_get_teachers($context->id);
    $toreturn = array();
    foreach ($teachers as $teacher) {
        $sql = "SELECT p.* FROM {socialwiki} w, {socialwiki_subwikis} s, {socialwiki_pages} p
                WHERE s.id = ? AND s.wikiid = w.id AND w.firstpagetitle = p.title AND p.subwikiid = s.id AND p.userid=?
                ORDER BY id ASC";
        $records = $DB->get_records_sql($sql, array($swid, $teacher->id));

        if ($records) {
            // Get the last edit of this page by the teacher.
            $toreturn[max(array_keys($records))] = $records[max(array_keys($records))];
        }
    }
    // If there are isn't a front page return false.
    if ($toreturn) {
        return $toreturn[max(array_keys($toreturn))];
    } else {
        return false;
    }
}

/**
 * Save a page section.
 *
 * @param stdClass $page The page that is modified.
 * @param string $sectiontitle The title of the section.
 * @param string $sectioncontent The content in the section.
 * @param int $uid The ID of the user.
 * @return bool|array
 */
function socialwiki_save_section($page, $sectiontitle, $sectioncontent, $uid) {
    $wiki = socialwiki_get_wiki_from_pageid($page->id);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    if (has_capability('mod/socialwiki:editpage', $context)) {
        $content = socialwiki_parser_proxy::get_section($page->content, $page->format, $sectiontitle, true);
        $newcontent = $content[0] . $sectioncontent . $content[2];

        return socialwiki_save_page($page, $newcontent, $uid);
    } else {
        return false;
    }
}

/**
 * Save page content.
 *
 * @param stdClass $page The page to modify.
 * @param string $newcontent The content in the page.
 * @param int $uid The ID of the user.
 * @return bool|array
 */
function socialwiki_save_page($page, $newcontent, $uid) {
    global $DB;

    $wiki = socialwiki_get_wiki_from_pageid($page->id);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    if (has_capability('mod/socialwiki:editpage', $context)) {
        $page->userid = $uid;
        $page->content = $newcontent;
        $DB->update_record('socialwiki_pages', $page);
        $options = array('swid' => $page->subwikiid, 'pageid' => $page->id);
        $parseroutput = socialwiki_parse_content($page->format, $newcontent, $options);
        return array('page' => $page, 'sections' => $parseroutput['repeated_sections']);
    } else {
        return false;
    }
}

/**
 * Restore a page.
 *
 * @param stdClass $page The page to modify.
 * @param string $newcontent The content in the page.
 * @param int $uid The ID of the user.
 * @return stdClass
 */
function socialwiki_restore_page($page, $newcontent, $uid) {
    $return = socialwiki_save_page($page, $newcontent, $uid);
    return $return['page'];
}

/**
 * Create a new wiki page, if the page exists, return existing pageid.
 *
 * @param int $swid The subwiki ID.
 * @param string $title The page title.
 * @param string $format The format type.
 * @param int $uid The user ID.
 * @param int $parent The parent page ID.
 * @return int
 */
function socialwiki_create_page($swid, $title, $format, $uid, $parent = null) {
    global $DB;
    $subwiki = socialwiki_get_subwiki($swid);
    $cm = get_coursemodule_from_instance('socialwiki', $subwiki->wikiid);
    $context = context_module::instance($cm->id);
    require_capability('mod/socialwiki:editpage', $context);

    // Creating a new empty page.
    $page = new stdClass();
    $page->subwikiid = $swid;
    $page->title = $title;
    $page->content = '';
    $page->timecreated = time();
    $page->format = $format;
    $page->userid = $uid;
    $page->pageviews = 0;
    $page->parent = $parent;

    $pid = $DB->insert_record('socialwiki_pages', $page);

    return $pid;
}

/**
 * Get pages list in wiki
 * @param int $swid subwiki ID.
 * @param bool $filter0likes Whether to skip the pages without likes.
 * @return stdClass[]
 */
function socialwiki_get_page_list($swid, $filter0likes = true) {
    global $DB;
    if ($filter0likes) {
        $sql = "SELECT DISTINCT p.* FROM {socialwiki_pages}
                AS p INNER JOIN {socialwiki_likes}
                AS l ON p.id=l.pageid WHERE p.subwikiid=?";
        $records = $DB->get_records_sql($sql, array("subwikiid" => $swid));
        return $records;
    } else {
        $records = $DB->get_records('socialwiki_pages', array('subwikiid' => $swid), 'title ASC');
        return $records;
    }
}

/**
 * Get the list of topics.
 *
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_topics($swid) {
    $records = socialwiki_get_page_list($swid);
    $pages = array();

    foreach ($records as $r) {
        if (!array_key_exists($r->title, $pages)) {
            $pages[$r->title] = array();
            $pages[$r->title]["Views"] = 0;
            $pages[$r->title]["Likes"] = 0;
            $pages[$r->title]["Versions"] = 0;
        }
        $pages[$r->title]["Views"] += intval($r->pageviews);
        $pages[$r->title]["Likes"] += intval(socialwiki_numlikes($r->id));
        $pages[$r->title]["Versions"] ++;
    }
    return $pages;
}

/**
 * Get a list of the user's pages.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_user_page_list($uid, $swid) {
    global $DB;
    $records = $DB->get_records('socialwiki_pages', array('subwikiid' => $swid, 'userid' => $uid), 'title ASC');
    return $records;
}

/**
 * Get a list of the user's topics.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_user_topics($uid, $swid) {
    $records = socialwiki_get_user_page_list($uid, $swid);
    $pages = array();

    foreach ($records as $r) {
        if (!array_key_exists($r->title, $pages)) {
            $pages[$r->title] = array();
            $pages[$r->title]["Views"] = 0;
            $pages[$r->title]["Likes"] = 0;
            $pages[$r->title]["Versions"] = 0;
        }
        $pages[$r->title]["Views"] += intval($r->pageviews);
        $pages[$r->title]["Likes"] += intval(socialwiki_numlikes($r->id));
        $pages[$r->title]["Versions"] ++;
    }
    return $pages;
}

/**
 * Gets all related pages to the title.
 *
 * @param int $swid The subwiki ID.
 * @param string $title The title to search for.
 * @return stdClass[]
 */
function socialwiki_get_related_pages($swid, $title) {
    global $DB;
    $sql = "SELECT p.id, p.title FROM {socialwiki_pages} p WHERE p.subwikiid = ? AND p.title = ?";
    return $DB->get_records_sql($sql, array($swid, $title));
}

/**
 * Search wiki title.
 *
 * @param int $swid The subwiki ID.
 * @param string $search What to search for.
 * @param bool $exact Only an exact match if true.
 * @return stdClass[]
 */
function socialwiki_search_title($swid, $search, $exact = false) {
    global $DB;
    $sql = "SELECT {socialwiki_pages}.*, COUNT(pageid) AS total
            FROM  {socialwiki_pages} LEFT JOIN  {socialwiki_likes}
            ON {socialwiki_pages}.id = {socialwiki_likes}.pageid
            WHERE {socialwiki_pages}.subwikiid=? AND ({socialwiki_pages}.title LIKE ?)
            GROUP BY {socialwiki_pages}.id ORDER BY total DESC";
    if ($exact) { // Exact title match.
        return $DB->get_records_sql($sql, array($swid, $search));
    } else {
        return $DB->get_records_sql($sql, array($swid, '%' . $search . '%'));
    }
}

/**
 * Search wiki content.
 *
 * @param int $swid The subwiki ID.
 * @param string $search What to search for.
 * @return stdClass[]
 */
function socialwiki_search_content($swid, $search) {
    global $DB;
    return $DB->get_records_select('socialwiki_pages', "subwikiid = ? AND content LIKE ?", array($swid, '%' . $search . '%'));
}

/**
 * Search wiki title and content.
 *
 * @param int $swid subwiki ID.
 * @param string $search What to search for.
 * @return stdClass[]
 */
function socialwiki_search_all($swid, $search) {
    global $DB;
    $sql = "SELECT {socialwiki_pages}.*, COUNT(pageid) AS total
            FROM  {socialwiki_pages}
            LEFT JOIN  {socialwiki_likes}  ON {socialwiki_pages}.id = {socialwiki_likes}.pageid
            WHERE {socialwiki_pages}.subwikiid=? AND ({socialwiki_pages}.content LIKE ? OR {socialwiki_pages}.title LIKE ?)
            GROUP BY {socialwiki_pages}.id
            ORDER BY total DESC";
    return $DB->get_records_sql($sql, array($swid, '%' . $search . '%', '%' . $search . '%'));
}

/**
 * Get user data.
 *
 * @param int $uid The user ID.
 * @return stdClass
 */
function socialwiki_get_user_info($uid) {
    global $DB;
    return $DB->get_record('user', array('id' => $uid));
}

/**
 * Increase page view number.
 *
 * @param stdClass $page Database record.
 */
function socialwiki_increment_pageviews($page) {
    global $DB;
    $page->pageviews++;
    $DB->update_record('socialwiki_pages', $page);
}

/**
 * Increase page view number for given user.
 * If this is the first time the user has viewed the page, a new entry will be added.
 *
 * @param int $uid The user ID.
 * @param int $pid The page ID.
 */
function socialwiki_increment_user_views($uid, $pid) {
    global $DB;

    $result = $DB->get_record('socialwiki_user_views', array('userid' => $uid, 'pageid' => $pid));
    if (!$result) {
        $DB->insert_record("socialwiki_user_views",
                array('userid' => $uid, 'pageid' => $pid, 'latestview' => time(), 'viewcount' => 1), true, false);
    } else {
        $userview = array(
            'id' => $result->id,
            'userid' => $result->userid,
            'pageid' => $result->pageid,
            'latestview' => time(),
            'viewcount' => $result->viewcount + 1,
        );
        $DB->update_record("socialwiki_user_views", $userview, false);
    }
}

// ----------------------------------------------------------
// ----------------------------------------------------------

/**
 * Style formats.
 *
 * @return string[]
 */
function socialwiki_get_styles() {
    return array('classic'); // Style 'modern' removed for now.
}

/**
 * Text format supported by wiki module.
 *
 * @return string[]
 */
function socialwiki_get_formats() {
    return array('html', 'creole', 'nwiki');
}

/**
 * Parses a string with the wiki markup language.
 *
 * @author Josep Arús Pous
 * @param string $markup The wiki markup language.
 * @param string $pagecontent The page content.
 * @param array $options Extra options.
 * @return bool|array False when something wrong has happened.
 */
function socialwiki_parse_content($markup, $pagecontent, $options = array()) {
    $subwiki = socialwiki_get_subwiki($options['swid']);
    $cm = get_coursemodule_from_instance("socialwiki", $subwiki->wikiid);
    $context = context_module::instance($cm->id);

    $parseroptions = array(
        'link_callback' => '/mod/socialwiki/locallib.php:socialwiki_parser_link',
        'link_callback_args' => array('swid' => $options['swid']),
        'table_callback' => '/mod/socialwiki/locallib.php:socialwiki_parser_table',
        'real_path_callback' => '/mod/socialwiki/locallib.php:socialwiki_parser_real_path',
        'real_path_callback_args' => array(
            'context' => $context,
            'component' => 'mod_socialwiki',
            'filearea' => 'attachments',
            'subwikiid' => $subwiki->id,
            'pageid' => $options['pageid']
        ),
        'pageid' => $options['pageid'],
        'pretty_print' => (isset($options['pretty_print']) && $options['pretty_print']),
        'printable' => (isset($options['printable']) && $options['printable'])
    );

    return socialwiki_parser_proxy::parse($pagecontent, $markup, $parseroptions);
}

/**
 * This function is the parser callback to parse wiki links.
 *
 * It returns the necessary information to print a link.
 *
 * NOTE: Empty pages and non-existent pages must be print in red color.
 *
 * !!!!!! IMPORTANT !!!!!!
 * It is critical that you call format_string on the content before it is used.
 *
 * @param string|page_wiki $link Name of a page.
 * @param array $options Extra options.
 * @return array Array('content' => string, 'url' => string, 'new' => bool, 'link_info' => array)
 */
function socialwiki_parser_link($link, $options = null) {
    // TODO: Doc return and options.
    global $CFG, $COURSE, $PAGE;

    $matches = array();

    if (is_object($link)) { // If the fn is passed a page_socialwiki object as 1st argument.
        $parsedlink = array('content' => $link->title, 'url' => $CFG->wwwroot . '/mod/socialwiki/view.php?pageid='
            . $link->id, 'new' => false, 'link_info' => array('link' => $link->title, 'pageid' => $link->id, 'new' => false));

        return $parsedlink;
    } else {
        $swid = $options['swid'];
        $specific = false;

        if (preg_match('/@(([0-9]+)|(\.))/', $link, $matches)) { // Retrieve a version?
            $link = preg_replace('/@(([0-9]+)|(\.))/', '', $link);
            $specific = true;
        }

        if ($page = socialwiki_get_page_by_title($swid, $link)) {
            if ($specific == false) { // Normal wikilink searching for pages by title.
                $currentpage = optional_param('pageid', 0, PARAM_INT);
                $parsedlink = array('content' => $link, 'url' => $CFG->wwwroot
                        . '/mod/socialwiki/search.php?searchstring=' . $link . '&pageid=' . $currentpage
                        . '&courseid=' . $COURSE->id . '&cmid=' . $PAGE->cm->id . '&exact=1', 'new' => false,
                    'link_info' => array('link' => $link, 'pageid' => -$page->id, 'new' => false));
            } else {
                if ($matches[1] == '.') {
                    $parsedlink = array('content' => $link, 'url' => $CFG->wwwroot
                            . '/mod/socialwiki/view.php?pageid=' . $page->id, 'new' => false,
                        'link_info' => array('link' => $link, 'pageid' => $page->id, 'new' => false));
                } else {

                    if (socialwiki_get_page($matches[1])) {
                        $parsedlink = array('content' => $link, 'url' => $CFG->wwwroot . '/mod/socialwiki/view.php?pageid='
                            . $matches[1], 'new' => false,
                            'link_info' => array('link' => $link, 'pageid' => $matches[1], 'new' => false));
                    } else {
                        $parsedlink = array('content' => $link, 'url' => $CFG->wwwroot . '/mod/socialwiki/view.php?pageid='
                            . socialwiki_get_first_page(socialwiki_get_subwiki($swid)->wikiid)->id,
                            'new' => false, 'link_info' => array('link' => $link, 'pageid' => $page->id, 'new' => false));
                    }
                }
            }

            return $parsedlink;
        } else {
            // May want to change what happens in here later,
            // kind of like the ability to make a link to a new page by just creating a link to it.
            return array('content' => $link, 'url' => $CFG->wwwroot . '/mod/socialwiki/create.php?swid='
                . $swid . '&amp;title=' . urlencode($link) . '&amp;action=new', 'new' => true,
                'link_info' => array('link' => $link, 'new' => true, 'pageid' => 0));
        }
    }
}

/**
 * Returns the table fully parsed (HTML).
 *
 * @param array $table Table Data.
 * @return HTML for the table $table
 * @author Josep Arús Pous
 *
 */
function socialwiki_parser_table($table) {
    $htmltable = new html_table();

    $headers = $table[0];
    $htmltable->head = array();
    foreach ($headers as $h) {
        $htmltable->head[] = $h[1];
    }

    array_shift($table);
    $htmltable->data = array();
    foreach ($table as $row) {
        $rowdata = array();
        foreach ($row as $r) {
            $rowdata[] = $r[1];
        }
        $htmltable->data[] = $rowdata;
    }

    return html_writer::table($htmltable);
}

/**
 * Returns an absolute path link, unless there is no such link.
 *
 * @param string $url Link's URL or filename
 * @param stdClass $context filearea params
 * @param string $component The component the file is associated with
 * @param string $filearea The filearea the file is stored in
 * @param int $swid Sub wiki id
 *
 * @return string URL for files full path
 */
function socialwiki_parser_real_path($url, $context, $component, $filearea, $swid) {
    global $CFG;

    if (preg_match("/^(?:http|ftp)s?\:\/\// ", $url)) {
        return $url;
    } else {

        $file = 'pluginfile.php';
        if (!$CFG->slasharguments) {
            $file = $file . '?file=';
        }
        $baseurl = "$CFG->wwwroot/$file/{$context->id}/$component/$filearea/$swid/";
        // It is a file in current file area.
        return $baseurl . $url;
    }
}

/**
 * Returns the token used by a wiki language to represent a given tag or "object" (bold -> **).
 *
 * @param string $markup The markup language.
 * @param string $name Type to check.
 * @return A string when it has only one token at the beginning (f. ex. lists).
 *         An array composed by 2 strings when it has 2 tokens, one at the beginning
 *         and one at the end (f. ex. italics). Returns false otherwise.
 * @author Josep Arús Pous
 */
function socialwiki_parser_get_token($markup, $name) {
    return socialwiki_parser_proxy::get_token($name, $markup);
}

/**
 * Checks if current user can view a subwiki.
 *
 * @param int $subwiki The subwiki ID.
 * @return bool
 */
function socialwiki_user_can_view($subwiki) {
    $wiki = socialwiki_get_wiki($subwiki->wikiid);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    // Working depending on activity groupmode.
    switch (groups_get_activity_groupmode($cm)) {
        case NOGROUPS:

            if ($wiki->wikimode == 'collaborative') {
                // Collaborative Mode:
                // There is one wiki for all the class.
                // Only view capbility needed.
                return has_capability('mod/socialwiki:viewpage', $context);
            } else {
                // Error.
                return false;
            }
        case SEPARATEGROUPS:
            // Collaborative and Individual Mode
            // Collaborative Mode: There is one wiki per group.
            // Individual Mode: Each person owns a wiki.
            if ($wiki->wikimode == 'collaborative' || $wiki->wikimode == 'individual') {
                // Only members of subwiki group could view that wiki.
                if (groups_is_member($subwiki->groupid)) {
                    // Only view capability needed.
                    return has_capability('mod/socialwiki:viewpage', $context);
                } else {
                    // User is not part of that group
                    // User must have: mod/wiki:managewiki capability
                    //              or moodle/site:accessallgroups capability
                    //             and mod/wiki:viewpage capability.
                    $view = has_capability('mod/socialwiki:viewpage', $context);
                    $manage = has_capability('mod/socialwiki:manage_socialwiki', $context);
                    $access = has_capability('moodle/site:accessallgroups', $context);
                    return ($manage || $access) && $view;
                }
            } else {
                // Error.
                return false;
            }
        case VISIBLEGROUPS:
            // Collaborative and Individual Mode
            // Collaborative Mode: There is one wiki per group.
            // Individual Mode: Each person owns a wiki.
            if ($wiki->wikimode == 'collaborative' || $wiki->wikimode == 'individual') {
                // Everybody can read all wikis
                // Only view capability needed.
                return has_capability('mod/socialwiki:viewpage', $context);
            } else {
                // Error.
                return false;
            }
        default: // Error.
            return false;
    }
}

/**
 * Checks if current user can edit a subwiki.
 *
 * @param int $subwiki The subwiki ID.
 * @return bool
 */
function socialwiki_user_can_edit($subwiki) {
    $wiki = socialwiki_get_wiki($subwiki->wikiid);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    // Working depending on activity groupmode.
    switch (groups_get_activity_groupmode($cm)) {
        case NOGROUPS:

            if ($wiki->wikimode == 'collaborative') {
                // Collaborative Mode: There is a wiki for all the class.
                // Only edit capbility needed.
                return has_capability('mod/socialwiki:editpage', $context);
            } else {
                // Error.
                return false;
            }
        case SEPARATEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // Collaborative Mode: There is one wiki per group.
                // Only members of subwiki group could edit that wiki.
                if ($subwiki->groupid == groups_get_activity_group($cm)) {
                    // Only edit capability needed.
                    return has_capability('mod/socialwiki:editpage', $context);
                } else {
                    // User is not part of that group
                    // User must have: mod/wiki:managewiki capability
                    //             and moodle/site:accessallgroups capability
                    //             and mod/wiki:editpage capability.
                    $manage = has_capability('mod/socialwiki:managewiki', $context);
                    $access = has_capability('moodle/site:accessallgroups', $context);
                    $edit = has_capability('mod/socialwiki:editpage', $context);
                    return $manage && $access && $edit;
                }
            } else {
                // Error.
                return false;
            }
        case VISIBLEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // Collaborative Mode: There is one wiki per group.
                // Only members of subwiki group could edit that wiki.
                if (groups_is_member($subwiki->groupid)) {
                    // Only edit capability needed.
                    return has_capability('mod/socialwiki:editpage', $context);
                } else { // User is not part of that group
                    // User must have: mod/wiki:managewiki capability
                    //             and mod/wiki:editpage capability.
                    $manage = has_capability('mod/socialwiki:managewiki', $context);
                    $edit = has_capability('mod/socialwiki:editpage', $context);
                    return $manage && $edit;
                }
            } else {
                // Error.
                return false;
            }
        default: // Error.
            return false;
    }
}

/**
 * Delete pages and all related data.
 *
 * @param mixed $context Context in which page needs to be deleted.
 * @param mixed $pages Pages to be deleted.
 * @param int $swid ID of the subwiki for which all pages should be deleted
 */
function socialwiki_delete_pages($context, $pages = null, $swid = null) {
    global $DB;

    if (!empty($pages) && is_int($pages)) {
        $pages = array($pages);
    } else if (!empty($swid)) {
        $pages = socialwiki_get_page_list($swid);
    }

    // If there is no pageid then return as we can't delete anything.
    if (empty($pages)) {
        return;
    }

    // Delete page and all it's relevent data.
    foreach ($pages as $p) {
        if (is_object($p)) {
            $p = $p->id;
        }

        // Delete page comments.
        $comments = socialwiki_get_comments($context->id, $p);
        foreach ($comments as $commentid => $commentvalue) {
            socialwiki_delete_comment($commentid, $context, $p);
        }

        // Delete page likes.
        socialwiki_delete_page_likes($p);

        // Delete page tags.
        $tags = tag_get_tags_array('socialwiki_pages', $p);
        foreach ($tags as $tagid => $tagvalue) {
            tag_delete_instance('socialwiki_pages', $p, $tagid);
        }

        // Delete page.
        $DB->delete_records('socialwiki_pages', array('id' => $p));
    }
}

/**
 * Get a comment.
 *
 * @param int $commentid The comment ID.
 * @return stdClass
 */
function socialwiki_get_comment($commentid) {
    global $DB;
    return $DB->get_record('comments', array('id' => $commentid));
}

/**
 * Returns all comments by context and pageid.
 *
 * @param int $contextid Current context ID.
 * @param int $pid Current page ID.
 * @return stdClass[]
 */
function socialwiki_get_comments($contextid, $pid) {
    global $DB;
    return $DB->get_records('comments', array('contextid' => $contextid, 'itemid' => $pid, 'commentarea' => 'socialwiki_page'));
}

/**
 * Add comments to database.
 *
 * @param stdClass $context Current context.
 * @param int $pid Current page ID.
 * @param string $content Content of the comment.
 * @param string $editor Version of editor we are using.
 */
function socialwiki_add_comment($context, $pid, $content, $editor) {
    global $CFG;
    require_once($CFG->dirroot . '/comment/lib.php');

    list($contextid, $course, $cm) = get_context_info_array($context->id);
    $cmt = new stdclass();
    $cmt->context = $contextid;
    $cmt->itemid = $pid;
    $cmt->area = 'socialwiki_page';
    $cmt->course = $course;
    $cmt->component = 'mod_socialwiki';

    $manager = new comment($cmt);

    if ($editor == 'creole') {
        $manager->add($content, SOCIALFORMAT_CREOLE);
    } else if ($editor == 'html') {
        $manager->add($content, FORMAT_HTML);
    } else if ($editor == 'nwiki') {
        $manager->add($content, SOCIALFORMAT_NWIKI);
    }
}

/**
 * Delete comments from database.
 *
 * @param int $commentid ID of comment which will be deleted.
 * @param stdClass $context Current context.
 * @param int $pid Current page ID.
 */
function socialwiki_delete_comment($commentid, $context, $pid) {
    global $CFG;
    require_once($CFG->dirroot . '/comment/lib.php');

    list($contextid, $course, $cm) = get_context_info_array($context->id);
    $cmt = new stdClass();
    $cmt->context = $contextid;
    $cmt->itemid = $pid;
    $cmt->area = 'socialwiki_page';
    $cmt->course = $course;
    $cmt->component = 'mod_socialwiki';

    $manager = new comment($cmt);
    $manager->delete($commentid);
}

/**
 * Delete all comments from the wiki
 */
function socialwiki_delete_comments_wiki() {
    global $PAGE, $DB;

    $cm = $PAGE->cm;
    $context = context_module::instance($cm->id);

    $table = 'comments';
    $select = 'contextid = ?';

    $DB->delete_records_select($table, $select, array($context->id));
}

/**
 * Print the page content.
 *
 * @param stdClass $page The current page.
 * @param stdClass $context The current context.
 * @param int $swid The subwiki ID.
 */
function socialwiki_print_page_content($page, $context, $swid) {
    global $PAGE, $USER;
    $content = socialwiki_parse_content($page->format, $page->content, array('swid' => $swid, 'pageid' => $page->id));
    $html = file_rewrite_pluginfile_urls($content['parsed_text'], 'pluginfile.php',
            $context->id, 'mod_socialwiki', 'attachments', $swid);
    $wikioutput = $PAGE->get_renderer('mod_socialwiki');
    // This is where the page content, from the title down, is rendered!
    echo $wikioutput->viewing_area(format_text($html, FORMAT_MOODLE, array('overflowdiv' => true, 'allowid' => true)), $page);

    // Only increment page view when linked, not refreshed.
    $pagerefreshed = (null !== filter_input(INPUT_SERVER, 'HTTP_CACHE_CONTROL'))
            && filter_input(INPUT_SERVER, 'HTTP_CACHE_CONTROL') === 'max-age=0';
    if (!$pagerefreshed) {
        socialwiki_increment_pageviews($page);
        socialwiki_increment_user_views($USER->id, $page->id);
    }
}

/**
 * Prints default edit form fields and buttons.
 *
 * @param string $format Edit form format (ex. creole).
 * @param int $pid The page ID.
 * @param bool $upload
 * @param array $deleteuploads
 */
function socialwiki_print_edit_form_default_fields($format, $upload = false, $deleteuploads = array()) {
    global $CFG, $PAGE, $OUTPUT;

    // Hidden values.
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    echo '<input type="hidden" name="format" value="' . $format . '"/>';

    // Attachments.
    require_once($CFG->dirroot . '/lib/form/filemanager.php');

    $filemanager = new moodlequickform_filemanager('attachments', get_string('wikiattachments', 'socialwiki'),
            array('id' => 'attachments'), array('subdirs' => false, 'maxfiles' => 99, 'maxbytes' => $CFG->maxbytes));

    $value = file_get_submitted_draft_itemid('attachments');
    if (!empty($value) && !$upload) {
        $filemanager->setvalue($value);
    }

    echo '<fieldset class="socialwiki-upload-section clearfix"><legend class="ftoggler">'
        . get_string("uploadtitle", 'socialwiki') . '</legend>';
    echo $OUTPUT->container_start('mdl-align socialwiki-form-center aaaaa');
    echo $filemanager->tohtml();
    echo $OUTPUT->container_end();

    $cm = $PAGE->cm;
    $context = context_module::instance($cm->id);

    echo $OUTPUT->container_start('mdl-align socialwiki-form-center socialwiki-upload-table');
    socialwiki_print_upload_table($context, 'socialwiki_upload', $value, $deleteuploads);
    echo $OUTPUT->container_end();
    echo "</fieldset>";

    $btnhtml = '<input class="socialwiki-button" type="submit" name="editoption" value="';
    echo $btnhtml . get_string('save', 'socialwiki') . '"/>';
    echo $btnhtml . get_string('upload', 'socialwiki') . '"/>';
    echo $btnhtml . get_string('preview') . '"/>';
    echo $btnhtml . get_string('cancel') . '" />';
}

/**
 * Prints a table with the files attached to a wiki page.
 *
 * @param stdClass $context Current context.
 * @param string $filearea Location of the file.
 * @param int $fileitemid The file ID.
 * @param array $deleteuploads
 */
function socialwiki_print_upload_table($context, $filearea, $fileitemid, $deleteuploads = array()) {
    global $CFG;

    $htmltable = new html_table();

    $htmltable->head = array(get_string('deleteupload', 'socialwiki'),
        get_string('uploadname', 'socialwiki'), get_string('uploadactions', 'socialwiki'));

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_socialwiki', $filearea, $fileitemid); // TODO: this is weird.

    foreach ($files as $file) {
        if (!$file->is_directory()) {
            $checkbox = '<input type="checkbox" name="deleteupload[]", value="' . $file->get_pathnamehash() . '"';

            if (in_array($file->get_pathnamehash(), $deleteuploads)) {
                $checkbox .= ' checked="checked"';
            }

            $checkbox .= " />";

            $htmltable->data[] = array($checkbox, '<a href="'
                . file_encode_url($CFG->wwwroot . '/pluginfile.php', '/' . $context->id . '/socialwiki_upload/'
                        . $fileitemid . '/' . $file->get_filename()) . '">' . $file->get_filename() . '</a>', "");
        }
    }

    echo '<h3 class="upload-table-title">' . get_string('uploadfiletitle', 'socialwiki') . "</h3>";
    echo html_writer::table($htmltable);
}

/**
 * Get updated pages from wiki.
 *
 * @param int $swid The subwiki ID.
 * @param int $uid The user ID.
 * @param bool $filterunseen Don't show the pages that have no views.
 * @return stdClass
 */
function socialwiki_get_updated_pages_by_subwiki($swid, $uid = '', $filterunseen = true) {
    global $DB, $USER;

    $sql = "SELECT *
            FROM {socialwiki_pages}
            WHERE subwikiid = ? AND timecreated > ?";
    $params = array($swid);
    if (isset($USER->lastlogin)) {
        $params[] = $USER->lastlogin;
    } else {
        $params[] = 0; // On first login, everything is new.
    }

    if ($filterunseen) {
        $sql = $sql . ' AND id NOT IN
                      (SELECT pageid FROM {socialwiki_user_views}
                       WHERE userid=?)';
        $params[] = $uid;
    }

    return $DB->get_records_sql($sql, $params);
}

/**
 * Returns all the people the user is following.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return int[]
 */
function socialwiki_get_follows($uid, $swid) {
    global $DB;
    $sql = 'SELECT usertoid
            FROM {socialwiki_follows}
            WHERE userfromid=? AND subwikiid=?';
    return $DB->get_records_sql($sql, array($uid, $swid));
}

/**
 * Checks if a user is following another user.
 *
 * @param int $userfromid The user doing the following.
 * @param int $usertoid The user being followed.
 * @param int $swid The subwiki ID.
 * @return bool
 */
function socialwiki_is_following($userfromid, $usertoid, $swid) {
    Global $DB;
    $sql = 'SELECT usertoid
            FROM {socialwiki_follows}
            WHERE userfromid=? AND usertoid=? AND subwikiid= ?';
    return $DB->record_exists_sql($sql, array($userfromid, $usertoid, $swid));
}

/**
 * Unfollow a user.
 *
 * @param int $userfromid The user doing the following.
 * @param int $usertoid The user being followed.
 * @param int $swid The subwiki ID.
 */
function socialwiki_unfollow($userfromid, $usertoid, $swid) {
    Global $DB;
    $select = 'userfromid=? AND usertoid=? AND subwikiid=?';
    $DB->delete_records_select('socialwiki_follows', $select, array($userfromid, $usertoid, $swid));
}

/**
 * Returns the number of people following the user.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return int
 */
function socialwiki_get_followers($uid, $swid) {
    Global $DB;
    $select = 'usertoid=? AND subwikiid=?';
    return count($DB->get_records_select('socialwiki_follows', $select, array($uid, $swid)));
}

/**
 * Returns the number of people following the user.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return int
 */
function socialwiki_get_follower_users($uid, $swid) {
    Global $DB;
    $sql = 'SELECT userfromid
            FROM {socialwiki_follows}
            WHERE usertoid=? AND subwikiid= ?';
    $results = $DB->get_records_sql($sql, array($uid, $swid));
    return array_map(function($obj) {
        return $obj->userfromid;
    }, $results);
}

/**
 * Returns the number of likes a page has.
 *
 * @param int $pid The page ID.
 * @return int
 */
function socialwiki_page_likes($pid) {
    global $DB;
    $sql = 'SELECT *
            FROM {socialwiki_likes}
            WHERE pageid=?';
    return $DB->record_exists_sql($sql, array($pid));
}

/**
 * Returns true if the user likes the page.
 *
 * @param int $uid The user ID.
 * @param int $pid The page ID.
 * @return bool
 */
function socialwiki_liked($uid, $pid) {
    global $DB;
    $sql = 'SELECT *
            FROM {socialwiki_likes}
            WHERE userid=? AND pageid=?';
    return $DB->record_exists_sql($sql, array($uid, $pid));
}

/**
 * Add a like.
 *
 * @param int $uid The user ID.
 * @param int $pid The page ID.
 * @param int $swid The subwiki ID.
 */
function socialwiki_add_like($uid, $pid, $swid) {
    Global $DB;
    $like = new stdClass();
    $like->userid = $uid;
    $like->pageid = $pid;
    $like->subwikiid = $swid;
    $DB->insert_record('socialwiki_likes', $like);
}

/**
 * Delete a like.
 *
 * @param int $uid The user ID.
 * @param int $pid The page ID.
 */
function socialwiki_delete_like($uid, $pid) {
    Global $DB;
    $select = 'userid=? AND pageid=?';
    $DB->delete_records_select('socialwiki_likes', $select, array($uid, $pid));
}

/**
 * Delete all of the likes a page has.
 *
 * @param int $pid The page ID.
 */
function socialwiki_delete_page_likes($pid) {
    Global $DB;
    $DB->delete_records_select('socialwiki_likes', 'pageid=?', array($pid));
}

/**
 * Get the number of likes for a page.
 *
 * @param int $pid The page ID.
 * @return int
 */
function socialwiki_numlikes($pid) {
    global $DB;
    $sql = 'SELECT *
            FROM {socialwiki_likes}
            WHERE pageid=?';
    return count($DB->get_records_sql($sql, array($pid)));
}

function socialwiki_page_like($uid, $pid, $swid) {
    if (socialwiki_liked($uid, $pid)) {
        socialwiki_delete_like($uid, $pid);
    } else {
        socialwiki_add_like($uid, $pid, $swid);
        // TODO: could optimize which peers we recompute: only those who have likes in common.
    }
    socialwiki_peer::socialwiki_update_peers(true, false, $swid, $uid); // Update like similarity to other peers.
    return socialwiki_numlikes($pid);
}

/**
 * Get all the pages from the users followed users.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @param bool $filterunseen Hide pages without likes.
 * @return stdClass[]
 */
function socialwiki_get_pages_from_followed($uid, $swid, $filterunseen = true) {
    global $DB;

    $sql = 'SELECT DISTINCT l.pageid
            FROM {socialwiki_follows} f INNER JOIN {socialwiki_likes} l
            ON f.usertoid=l.userid
            WHERE f.userfromid=? AND l.subwikiid=? AND f.subwikiid=?';
    $params = array($uid, $swid, $swid);
    if ($filterunseen) {
        $sql = $sql . 'AND NOT EXISTS
                      (SELECT 1 FROM {socialwiki_user_views} v
                       WHERE v.userid=? and v.pageid=l.pageid)';
        $params[] = $uid;
    }
    $results = $DB->get_records_sql($sql, $params);
    return array_map(function($a) {
        return socialwiki_get_page($a->pageid);
    }, $results);
}

/**
 * Return all the pages the user likes.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_user_likes($uid, $swid) {
    global $DB;
    $sql = 'SELECT pageid
            FROM {socialwiki_likes}
            WHERE userid=? and subwikiid=?';
    return $DB->get_records_sql($sql, array($uid, $swid));
}

/**
 * Return an array of all the users that like a page.
 *
 * @param int $pid The page ID.
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_page_likes($pid, $swid) {
    global $DB;
    $sql = 'SELECT userid
            FROM {socialwiki_likes}
            WHERE pageid=? and subwikiid=?';
    $res = $DB->get_records_sql($sql, array($pid, $swid), 0, 1000);

    return array_map(function($a) {
        return $a->userid;
    }, $res);
}

/**
 * Get page's author.
 *
 * @param int $pid The page ID.
 * @return string
 */
function socialwiki_get_author($pid) {
    global $DB;
    $sql = 'SELECT userid
            FROM {socialwiki_pages}
            WHERE id=?';
    return $DB->get_record_sql($sql, array($pid));
}

/**
 * Get pages favourited by a user.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_user_favourites($uid, $swid) {
    $results = socialwiki_get_user_likes($uid, $swid);
    $favourites = array();
    foreach ($results as $r) {
        if (socialwiki_is_user_favourite($uid, $r->pageid, $swid)) {
            array_push($favourites, socialwiki_get_page($r->pageid));
        }
    }
    return $favourites;
}

/**
 * Get all users who favourite this page.
 *
 * @param int $pid The page ID.
 * @param int $swid The subwiki ID.
 * @return int[]
 */
function socialwiki_get_page_favourites($pid, $swid) {
    $likers = socialwiki_get_page_likes($pid, $swid);
    $favourites = array();
    foreach ($likers as $userid) {
        if (socialwiki_is_user_favourite($userid, $pid, $swid)) {
            array_push($favourites, $userid);
        }
    }
    return $favourites;
}

/**
 * Check if a page is a user's favorite.
 *
 * @param int $uid The user ID.
 * @param int $pid The page ID.
 * @param int $swid The subwiki ID.
 * @return boolean
 */
function socialwiki_is_user_favourite($uid, $pid, $swid) {
    global $DB;
    $page = socialwiki_get_page($pid);
    $sql = 'SELECT pageid
            FROM {socialwiki_likes} l
            INNER JOIN {socialwiki_pages} p
            ON l.pageid=p.id
            WHERE l.userid=? and p.title=? and l.subwikiid=?
            ORDER BY p.timecreated DESC LIMIT 1';
    $out = $DB->get_record_sql($sql, array($uid, $page->title, $swid));
    if (isset($out->pageid)) {
        return ($out->pageid == $pid);
    }
    return false;
}

/**
 * Get the ID of the parent page.
 *
 * @param int $pid The page ID.
 * @return stdClass
 */
function socialwiki_get_parent($pid) {
    Global $DB;
    $sql = 'SELECT parent
            FROM {socialwiki_pages}
            WHERE id=?';
    return $DB->get_record_sql($sql, array($pid));
}

/**
 * Get all contributors, traverse the parent links to the root.
 *
 * @param int $pid The page ID.
 * @return stdClass[]
 */
function socialwiki_get_contributors($pid) {
    Global $DB;
    $sql = 'SELECT userid, parent
            FROM {socialwiki_pages}
            WHERE id=?';
    $result = $DB->get_record_sql($sql, array($pid));

    if (isset($result->parent)) {
        $contribs = socialwiki_get_contributors($result->parent); // Recursion.
    } else {
        $contribs = array();
    }

    if (isset($result->userid)) {
        $contribs = array_diff($contribs, array($result->userid));
        $contribs[] = $result->userid;
    }

    return $contribs;
}

/**
 * Get all the children pages of a page.
 *
 * @param int $pid The page ID.
 * @return stdClass[]
 */
function socialwiki_get_children($pid) {
    Global $DB;
    $sql = 'SELECT *
            FROM {socialwiki_pages}
            WHERE parent=?';
    return $DB->get_records_sql($sql, array($pid));
}

/**
 * Get all the users of the subwiki.
 *
 * @param int $swid The subwiki ID.
 * @return int[]
 */
function socialwiki_get_subwiki_users($swid) {
    Global $PAGE;
    $context = context_module::instance($PAGE->cm->id);
    $users = get_enrolled_users($context);
    $uids = array();
    foreach ($users as $u) {
        array_push($uids, $u->id);
    }
    return $uids;
}

/**
 * Get all the users who have contributed to the subwiki.
 *
 * @param int $swid The subwiki ID.
 * @return int[]
 */
function socialwiki_get_active_subwiki_users($swid) {
    Global $DB;
    $sql = 'SELECT DISTINCT v.userid
            FROM {socialwiki_user_views} v
            JOIN {socialwiki_pages} p ON v.pageid=p.id WHERE p.subwikiid=?';

    $users = $DB->get_records_sql($sql, array($swid));
    $uids = array();
    foreach ($users as $u) {
        $uids[] = $u->userid;
    }
    return $uids;
}

/**
 * Returns an array with all the parent and child pages.
 *
 * @param int $pid The page ID.
 * @return stdClass[]
 */
function socialwiki_get_relations($pid) {
    $relations = array();
    $added = array(); // An array of page id's already added to $relations.
    // Add all parents up to root node.
    while ($pid != null && $pid != 0) {
        $relations[] = socialwiki_get_page($pid);
        $added[] = $pid;
        $pid = socialwiki_get_parent($pid)->parent;
    }
    // Add all the children.
    for ($i = 0; $i < count($relations); $i++) {
        $pages = socialwiki_get_children($relations[$i]->id);
        foreach ($pages as $page) {
            // Make sure it hasn't already been added.
            if (!in_array($page->id, $added)) {
                $relations[] = socialwiki_get_page($page->id);
            }
        }
    }
    sort($relations);
    return $relations;
}

/**
 * Returns the current style of the socialwiki.
 *
 * @param int $swid The wiki ID.
 * @return string
 */
function socialwiki_get_currentstyle($swid) {
    Global $DB;
    $sql = 'SELECT style FROM {socialwiki} WHERE id=?';
    return $DB->get_record_sql($sql, array($swid));
}

/**
 * Returns the index of a page given page id and an array of pages, -1 if not found.
 *
 * @param int $pid The page ID.
 * @param array $pages Set of pages.
 * @return int
 */
function socialwiki_indexof_page($pid, $pages) {
    for ($i = 0; $i < count($pages); $i++) {
        if ($pages[$i]->id == $pid) {
            return $i;
        }
    }
    return -1;
}

/**
 * Returns array of teachers as moodle allows multiple teachers per course.
 *
 * @param int $contextid The current context ID.
 * @return stdClass[]
 */
function socialwiki_get_teachers($contextid) {
    Global $DB;
    $sql = 'SELECT ra.userid AS id
            FROM {role_assignments} ra
            JOIN {role} r ON r.id=ra.roleid
            WHERE contextid=? AND (shortname=? OR shortname=?)';
    return $DB->get_records_sql($sql, array($contextid, 'teacher', 'editingteacher'));
}

/**
 * Checks if the user is a teacher.
 *
 * @param int $contextid The current context ID.
 * @param int $uid The current user ID.
 * @return bool
 */
function socialwiki_is_teacher($contextid, $uid) {
    $teachers = socialwiki_get_teachers($contextid);
    foreach ($teachers as $teacher) {
        if ($uid == $teacher->id) {
            return true;
        }
    }
    return false;
}

/**
 * Returns an array of pages chosen based on peers likes and follows.
 *
 * @param int $uid The user ID.
 * @param int $swid The subwiki ID.
 * @return stdClass[]
 */
function socialwiki_get_recommended_pages($uid, $swid) {
    Global $CFG;
    require_once($CFG->dirroot . '/mod/socialwiki/peer.php');
    $scale = array('follow' => 1, 'like' => 1, 'trust' => 1, 'popular' => 1); // Scale with weight for each peer category.
    $peers = socialwiki_get_peers($swid, $scale); // TODO: not sure if this does anything...
    $pages = socialwiki_get_page_list($swid);

    foreach ($pages as $page) {
        if (socialwiki_liked($uid, $page->id)) {
            unset($pages[$page->id]);
            continue;
        }
        $votes = $page->timecreated / time();
        foreach ($peers as $peer) {
            if (socialwiki_liked($peer->id, $page->id)) {
                $votes += $peer->score;
            }
        }
        $page->votes = $votes;
    }
    // Sort pages based on votes.
    usort($pages, "socialwiki_page_comp");

    // Return top ten pages.
    if (count($pages) <= 20) {
        return($pages);
    } else {
        return array_slice($pages, 0, 20);
    }
}

/**
 * Used to sort pages based on votes attribute.
 *
 * @param stdClass $p1 A page.
 * @param stdClass $p2 A second page.
 * @return int
 */
function socialwiki_page_comp($p1, $p2) {
    if ($p1->votes == $p2->votes) {
        return 0;
    }
    return ($p1->votes < $p2->votes) ? 1 : -1;
}

/**
 * Sorts an array of pages by likes.
 *
 * @param array $pages Set of pages.
 * @return stdClass[]
 */
function socialwiki_order_by_likes($pages) {
    foreach ($pages as $page) {
        $page->votes = socialwiki_numlikes($page->id);
    }
    usort($pages, "socialwiki_page_comp");
    return $pages;
}

/**
 * Merge sort for leaf nodes.
 *
 * @param array $array
 * @return array
 */
function socialwiki_merge_sort_nodes($array) {
    if (count($array) <= 1) {
        return $array;
    }

    $left = socialwiki_merge_sort_nodes(array_slice($array, 0, (int) (count($array) / 2)));
    $right = socialwiki_merge_sort_nodes(array_slice($array, (int) (count($array) / 2)));

    return socialwiki_merge_nodes($left, $right);
}

/**
 * Merge two seperate nodes.
 *
 * @param node $left
 * @param node $right
 * @return array
 */
function socialwiki_merge_nodes($left, $right) {
    $result = array();
    while (count($left) > 0 && count($right) > 0) {
        if ($left[0]->priority >= $right[0]->priority) {
            array_push($result, array_shift($left));
        } else {
            array_push($result, array_shift($right));
        }
    }

    array_splice($result, count($result), 0, $left);
    array_splice($result, count($result), 0, $right);

    return $result;
}

/**
 * Orders pages using the trust indicators from an array of peers also sends peers to JavaScript.
 *
 * @param array $peers Peer objects.
 * @param array $pages Set of pages.
 * @param array $scale Scale of each indicator.
 * @return stdClass[]
 */
function socialwiki_order_pages_using_peers($peers, $pages, $scale) {
    foreach ($pages as $page) {
        $page->trust = 0;
        $page->time = $page->timecreated / time();
        $page->likesim = 0;
        $page->followsim = 0;
        $page->peerpopular = 0;
        $page->votes = $page->time;

        foreach ($peers as $peer) {
            if (socialwiki_liked($peer->id, $page->id)) {
                $page->votes += $peer->score;
                $page->trust += $peer->trust * $scale['trust'];
                $page->likesim += $peer->likesim * $scale['like'];
                $page->followsim += $peer->followsim * $scale['follow'];
                $page->peerpopular += $peer->popularity * $scale['popular'];
            }
        }
    }
    usort($pages, "socialwiki_page_comp");
    return $pages;
}

/**
 * Finds the following depth for a user.
 *
 * @param int $userfrom
 * @param int $userto
 * @param int $swid The subwiki ID.
 * @param int $depth
 * @param int[] $checked An array of users that have already been checked.
 * @return int
 */
function socialwiki_follow_depth($userfrom, $userto, $swid, $depth = 1, &$checked = array()) {
    if (socialwiki_is_following($userfrom, $userto, $swid)) {
        return $depth;
    }
    // Get userfrom's follows.
    $follows = socialwiki_get_follows($userfrom, $swid);
    if (count($follows > 0)) {
        // Add the userfrom to checked array.
        $checked[] = $userfrom;
        $depth++;
        foreach ($follows as $follow) {
            // Keep checking until either all followers have been checked or a follower is following userto.
            if (!in_array($follow->usertoid, $checked)) {
                $fdepth = socialwiki_follow_depth($follow->usertoid, $userto, $swid, $depth, $checked);
                if ($fdepth != 0) {
                    return $fdepth;
                }
            }
        }
    }
    return 0;
}

/**
 * Gives the time in a readable format.
 *
 * @param int $time The time in system format.
 * @param bool $timeago If true, format how long ago instead of date.
 * @return string
 */
function socialwiki_format_time($time, $timeago = true) {
    // Standard month, day, year format.
    if (!$timeago) {
        return strftime('%d %b %Y', $time);
    }

    // Return the time based upon how long ago from the current time.
    $diff = (new DateTime)->diff(new DateTime('@' . $time));
    $types = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    // Loops through to return the first type available.
    foreach ($types as $t => &$i) {
        if ($diff->$t) {
            return $diff->$t . ' ' . $i . ($diff->$t > 1 ? 's' : '') . ' ago';
        }
    }
}
