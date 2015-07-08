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
 * Manage files in wiki
 *
 * @package   mod_socialwiki
 * @copyright 2011 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('locallib.php');
require_once("$CFG->dirroot/mod/socialwiki/filesedit_form.php");
require_once("$CFG->dirroot/repository/lib.php");

$subwikiid = required_param('subwiki', PARAM_INT);
// Not being used for file management, we use it to generate navbar link.
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if (!$subwiki = socialwiki_get_subwiki($subwikiid)) {
    print_error('incorrectsubwikiid', 'socialwiki');
}

// Checking wiki instance of that subwiki.
if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'socialwiki');
}

// Checking course module instance.
if (!$cm = get_coursemodule_from_instance("socialwiki", $subwiki->wikiid)) {
    print_error('invalidcoursemodule');
}

// Checking course instance.
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/socialwiki:managefiles', $context);

if (empty($returnurl)) {
    if (!empty(filter_input(INPUT_SERVER, 'HTTP_REFERER'))) {
        $returnurl = filter_input(INPUT_SERVER, 'HTTP_REFERER');
    } else {
        $returnurl = new moodle_url('/mod/socialwiki/files.php', array('swid' => $subwiki->id));
    }
}

$title = get_string('editfiles', 'socialwiki');

$struser = get_string('user');
$url = new moodle_url('/mod/socialwiki/filesedit.php', array('subwiki' => $subwiki->id));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(format_string(get_string('wikifiles', 'socialwiki')), $CFG->wwwroot
        . '/mod/socialwiki/files.php?swid=' . $subwikiid);
$PAGE->navbar->add(format_string($title));

$data = new stdClass();
$data->returnurl = $returnurl;
$data->subwikiid = $subwiki->id;
$maxbytes = get_max_upload_file_size($CFG->maxbytes, $COURSE->maxbytes);
$options = array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => -1,
    'accepted_types' => '*', 'return_types' => FILE_INTERNAL | FILE_REFERENCE);
file_prepare_standard_filemanager($data, 'files',
        $options, $context, 'mod_socialwiki', 'attachments', $subwiki->id);

$mform = new mod_socialwiki_filesedit_form(null, array('data' => $data, 'options' => $options));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    $formdata = file_postupdate_standard_filemanager($formdata, 'files',
            $options, $context, 'mod_socialwiki', 'attachments', $subwiki->id);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
