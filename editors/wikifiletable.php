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
 * This file contains all necessary code to define a wiki file table form element
 *
 * @package mod-wiki-2.0
 * @copyright 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyright 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Josep Arus
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('HTML/QuickForm/element.php');
require_once($CFG->dirroot . '/lib/filelib.php');

class moodlequickform_socialwikifiletable extends HTML_QuickForm_element {

    private $_contextid;
    private $_filearea;
    private $_fileareaitemid;
    private $_fileinfo;
    private $_value = array();

    public function __construct($elementname = null, $elementlabel = null, $attributes = null, $fileinfo = null, $format = null) {
        parent::__construct($elementname, $elementlabel, $attributes);
        $this->_fileinfo = $fileinfo;
        $this->_format = $format;
    }

    public function onquickformevent($event, $arg, &$caller) {
        switch ($event) {
            case 'addElement':
                $this->_contextid = $arg[3]['contextid'];
                $this->_filearea = $arg[3]['filearea'];
                $this->_fileareaitemid = $arg[3]['itemid'];
                $this->_format = $arg[4];
                break;
        }

        return parent::onquickformevent($event, $arg, $caller);
    }

    public function setname($name) {
        $this->updateAttributes(array('name' => $name));
    }

    public function getname() {
        return $this->getAttribute('name');
    }

    public function setvalue($value) {
        $this->_value = $value;
    }

    public function getvalue() {
        return $this->_value;
    }

    public function tohtml() {
        global $CFG, $OUTPUT;

        $htmltable = new html_table();

        $htmltable->head = array(get_string('deleteupload', 'socialwiki'),
            get_string('uploadname', 'socialwiki'), get_string('uploadactions', 'socialwiki'));

        $fs = get_file_storage();

        $files = $fs->get_area_files($this->_fileinfo['contextid'], 'mod_socialwiki', 'attachments', $this->_fileinfo['itemid']);
        // TODO: verify where this is coming from, all params must be validated (skodak).

        if (count($files) < 2) {
            return get_string('noattachments', 'socialwiki');
        }

        // Get tags.
        foreach (array('image', 'attach', 'link') as $tag) {
            $tags[$tag] = socialwiki_parser_get_token($this->_format, $tag);
        }

        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $checkbox = '<input type="checkbox" name="' . $this->_attributes['name']
                        . '[]" value="' . $file->get_pathnamehash() . '"';

                if (in_array($file->get_pathnamehash(), $this->_value)) {
                    $checkbox .= ' checked="checked"';
                }
                $checkbox .= " />";

                // Actions.
                $icon = file_file_icon($file);
                $fileurl = file_encode_url($CFG->wwwroot . '/pluginfile.php',
                        "/{$this->_contextid}/mod_socialwiki/attachments/{$this->_fileareaitemid}/" . $file->get_filename());

                $actionicons = "";
                if (!empty($tags['attach'])) {
                    $actionicons .= "<a href=\"javascript:void(0)\" class=\"socialwiki-attachment-attach\" "
                            . $this->printinserttags($tags['attach'], $file->get_filename()) . " title=\""
                            . get_string('attachmentattach', 'socialwiki') . "\">"
                            . $OUTPUT->pix_icon($icon, "Attach") . "</a>"; // TODO: localize.
                }

                $actionicons .= "&nbsp;&nbsp;<a href=\"javascript:void(0)\" class=\"socialwiki-attachment-link\" "
                        . $this->printinserttags($tags['link'], $fileurl) . " title=\""
                        . get_string('attachmentlink', 'socialwiki') . "\">"
                        . $OUTPUT->pix_icon($icon, "Link") . "</a>";

                if (file_mimetype_in_typegroup($file->get_mimetype(), 'web_image')) {
                    $actionicons .= "&nbsp;&nbsp;<a href=\"javascript:void(0)\" class=\"socialwiki-attachment-image\" "
                            . $this->printinserttags($tags['image'], $file->get_filename()) . " title=\""
                            . get_string('attachmentimage', 'socialwiki') . "\">"
                            . $OUTPUT->pix_icon($icon, "Image") . "</a>"; // TODO: localize.
                }

                $htmltable->data[] = array($checkbox, '<a href="' . $fileurl . '">'
                    . $file->get_filename() . '</a>', $actionicons);
            }
        }

        return html_writer::table($htmltable);
    }

    private function printinserttags($tags, $value) {
        return "onclick=\"javascript:insertTags('{$tags[0]}', '{$tags[1]}', '$value');\"";
    }

}

// Register wikieditor.
moodlequickform::registerElementType('socialwikifiletable', $CFG->dirroot
        . "/mod/socialwiki/editors/wikifiletable.php", 'moodlequickform_socialwikifiletable');

