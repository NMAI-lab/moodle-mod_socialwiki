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
 * The help page.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require($CFG->dirroot . '/mod/socialwiki/locallib.php');

$id = required_param('id', PARAM_INT);

// Checking course module instance.
if (!$cm = get_coursemodule_from_id('socialwiki', $id)) {
    print_error('invalidcoursemodule', 'socialwiki');
}

// Checking course instance.
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course, true, $cm);

// Checking socialwiki instance.
if (!$wiki = socialwiki_get_wiki($cm->instance)) {
    print_error('incorrectwikiid', 'socialwiki');
}

$PAGE->set_cm($cm);
$PAGE->set_context(context_module::instance($cm->id));
$PAGE->requires->css(new moodle_url("/mod/socialwiki/{$wiki->style}_style.css"));

$wikioutput = $PAGE->get_renderer('mod_socialwiki');

echo $OUTPUT->header();
echo $wikioutput->content_area_begin();
echo $OUTPUT->heading('Help Page', 1);
echo $wikioutput->help_content('Home');
echo $wikioutput->help_content('Edit');
echo $wikioutput->help_content('Search');
echo $wikioutput->content_area_end();
echo $OUTPUT->footer();