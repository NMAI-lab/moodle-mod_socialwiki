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
 * This file contains all necessary code to define a wiki editor
 *
 * @package mod_socialwiki
 * @copyright 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyright 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Josep Arus
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/form/textarea.php');

class moodlequickform_socialwikieditor extends moodlequickform_textarea {

    private $files;

    public function moodlequickform_socialwikieditor($elementname = null, $elementlabel = null, $attributes = null) {
        if (isset($attributes['socialwiki_format'])) {
            $this->wikiformat = $attributes['socialwiki_format'];
            unset($attributes['socialwiki_format']);
        }
        if (isset($attributes['files'])) {
            $this->files = $attributes['files'];
            unset($attributes['files']);
        }

        parent::__construct($elementname, $elementlabel, $attributes);
    }

    public function setwikiformat($wikiformat) {
        $this->wikiformat = $wikiformat;
    }

    public function tohtml() {
        $textarea = parent::tohtml();

        return $this->{$this->wikiformat . "Editor"}($textarea);
    }

    public function creoleeditor($textarea) {
        return $this->printwikieditor($textarea);
    }

    public function nwikieditor($textarea) {
        return $this->printwikieditor($textarea);
    }

    private function printwikieditor($textarea) {
        global $OUTPUT;

        $textarea = $OUTPUT->container_start() . $textarea . $OUTPUT->container_end();

        $buttons = $this->getbuttons();

        return $buttons . $textarea;
    }

    private function getbuttons() {
        global $PAGE, $OUTPUT, $CFG;

        $editor = $this->wikiformat;

        $tag = $this->gettockens($editor, 'bold');
        $wikieditor['bold'] = array('ed_bold.gif', get_string('wikiboldtext', 'socialwiki'),
            $tag[0], $tag[1], get_string('wikiboldtext', 'socialwiki'));

        $tag = $this->gettockens($editor, 'italic');
        $wikieditor['italic'] = array('ed_italic.gif', get_string('wikiitalictext', 'socialwiki'),
            $tag[0], $tag[1], get_string('wikiitalictext', 'socialwiki'));

        $imagetag = $this->gettockens($editor, 'image');
        $wikieditor['image'] = array('ed_img.gif', get_string('wikiimage', 'socialwiki'),
            $imagetag[0], $imagetag[1], get_string('wikiimage', 'socialwiki'));

        $tag = $this->gettockens($editor, 'link');
        $wikieditor['internal'] = array('ed_internal.gif', get_string('wikiinternalurl', 'socialwiki'),
            $tag[0], $tag[1], get_string('wikiinternalurl', 'socialwiki'));

        $tag = $this->gettockens($editor, 'url');
        $wikieditor['external'] = array('ed_external.gif', get_string('wikiexternalurl', 'socialwiki'),
            $tag, "", get_string('wikiexternalurl', 'socialwiki'));

        $tag = $this->gettockens($editor, 'list');
        $wikieditor['u_list'] = array('ed_ul.gif', get_string('wikiunorderedlist', 'socialwiki'), '\\n' . $tag[0], '', '');
        $wikieditor['o_list'] = array('ed_ol.gif', get_string('wikiorderedlist', 'socialwiki'), '\\n' . $tag[1], '', '');

        $tag = $this->gettockens($editor, 'header');
        $wikieditor['h1'] = array('ed_h1.gif', get_string('wikiheader', 'socialwiki', 1),
            '\\n' . $tag . ' ', ' ' . $tag . '\\n', get_string('wikiheader', 'socialwiki', 1));
        $wikieditor['h2'] = array('ed_h2.gif', get_string('wikiheader', 'socialwiki', 2),
            '\\n' . $tag . $tag . ' ', ' ' . $tag . $tag . '\\n', get_string('wikiheader', 'socialwiki', 2));
        $wikieditor['h3'] = array('ed_h3.gif', get_string('wikiheader', 'socialwiki', 3),
            '\\n' . $tag . $tag . $tag . ' ', ' ' . $tag . $tag . $tag . '\\n', get_string('wikiheader', 'socialwiki', 3));

        $tag = $this->gettockens($editor, 'line_break');
        $wikieditor['hr'] = array('ed_hr.gif', get_string('wikihr', 'socialwiki'), '\\n' . $tag . '\\n', '', '');

        $tag = $this->gettockens($editor, 'nowiki');
        $wikieditor['nowiki'] = array('ed_nowiki.gif', get_string('wikinowikitext', 'socialwiki'),
            $tag[0], $tag[1], get_string('wikinowikitext', 'socialwiki'));

        $PAGE->requires->js(new moodle_url('/mod/socialwiki/editors/wiki/buttons.js'));

        $html = '<div class="socialwikieditor-toolbar">';
        foreach ($wikieditor as $button) {
            $html .= "<a href=\"javascript:insertTags('$button[2]','$button[3]','$button[4]');\">";
            $html .= html_writer::empty_tag('img', array('alt' => $button[1], 'src' => $CFG->wwwroot
                        . '/mod/socialwiki/editors/wiki/images/' . $button[0]));
            $html .= "</a>";
        }
        $html .= "<label class='accesshide' for='addtags'>"
                . get_string('insertimage', 'socialwiki') . "</label>";
        $html .= "<select id='addtags' onchange=\"insertTags('$imagetag[0]', '$imagetag[1]', this.value)\">";
        $html .= "<option value='" . s(get_string('wikiimage', 'socialwiki')) . "'>"
                . get_string('insertimage', 'socialwiki') . '</option>';
        foreach ($this->files as $filename) {
            $html .= "<option value='" . s($filename) . "'>";
            $html .= $filename;
            $html .= '</option>';
        }
        $html .= '</select>';
        $html .= $OUTPUT->help_icon('insertimage', 'socialwiki');
        $html .= '</div>';

        return $html;
    }

    private function gettockens($format, $token) {
        $tokens = socialwiki_parser_get_token($format, $token);

        if (is_array($tokens)) {
            foreach ($tokens as & $t) {
                $this->escapetoken($t);
            }
        } else {
            $this->escapetoken($tokens);
        }

        return $tokens;
    }

    private function escapetoken(&$token) {
        $token = urlencode(str_replace("'", "\'", $token));
    }

}

// Register wikieditor.
moodlequickform::registerElementType('socialwikieditor', $CFG->dirroot
        . "/mod/socialwiki/editors/wikieditor.php", 'moodlequickform_socialwikieditor');
