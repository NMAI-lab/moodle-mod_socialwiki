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
 * This page lists all the instances of wiki in a particular course
 *
 * @package mod_socialwiki
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
require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // Course ID.
$PAGE->set_url('/mod/socialwiki/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_login($course, true);
$PAGE->set_pagelayout('incourse');
$context = context_course::instance($course->id);

// Get all required stringswiki.
$strwikis = get_string("modulenameplural", "socialwiki");
$strwiki = get_string("modulename", "socialwiki");

// Print the header.
$PAGE->navbar->add($strwikis, "index.php?id=$course->id");
$PAGE->set_title($strwikis);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Get all the appropriate data.
if (!$wikis = get_all_instances_in_course("socialwiki", $course)) {
    notice("There are no social wikis", "../../course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);

// Print the list of instances (your module will probably extend this).

$timenow = time();
$strsectionname = get_string('sectionname', 'format_' . $course->format);
$strname = get_string("name");
$table = new html_table();

if ($usesections) {
    $table->head = array($strsectionname, $strname);
} else {
    $table->head = array($strname);
}

foreach ($wikis as $wiki) {
    $linkcss = null;
    if (!$wiki->visible) {
        $linkcss = array('class' => 'dimmed');
    }
    $link = html_writer::link(new moodle_url('/mod/socialwiki/view.php',
            array('id' => $wiki->coursemodule)), $wiki->name, $linkcss);

    if ($usesections) {
        $table->data[] = array(get_section_name($course, $wiki->section), $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
