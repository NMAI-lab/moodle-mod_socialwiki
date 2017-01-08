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

class MoodleQuickForm_socialwikieditor extends MoodleQuickForm_textarea {

    private $files;
    private $wikiformat;

    public function __construct($elementname = null, $elementlabel = null, $attributes = null) {
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

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function MoodleQuickForm_socialwikieditor($elementname = null, $elementlabel = null, $attributes = null) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($elementname, $elementlabel, $attributes);
    }

    public function setwikiformat($wikiformat) {
        $this->wikiformat = $wikiformat;
    }

    public function tohtml() {
        return $this->{$this->wikiformat . "Editor"}(parent::toHtml());
    }

    public function creoleeditor($textarea) {
        return $this->printwikieditor($textarea);
    }

    public function nwikieditor($textarea) {
        return $this->printwikieditor($textarea);
    }

    private function printwikieditor($textarea) {
        return $this->getbuttons() . $textarea;
    }

    private function getbuttons() {
        global $PAGE, $OUTPUT;

        $PAGE->requires->js(new moodle_url('/mod/socialwiki/editors/wikieditor.js'));
        $editor = $this->wikiformat;

        $html = '<div class="editor_atto_toolbar socialwikieditor-toolbar">';

        // Styleprops, Bold, Italics.
        $html .= '<div class="atto_group style1_group">';

        $html .= html_writer::start_tag('div', array('id' => 'styleheads'));
        $html .= html_writer::start_tag('ul', array('class' => 'dropdown-menu'));
        $tag = $this->gettockens($editor, 'header');
        $html .= $this->makeitem(get_string('wikiheader', 'socialwiki', 1), "\n$tag ", " $tag\n");
        $html .= $this->makeitem(get_string('wikiheader', 'socialwiki', 2), "\n$tag$tag ", " $tag$tag\n");
        $html .= $this->makeitem(get_string('wikiheader', 'socialwiki', 3), "\n$tag$tag$tag ", " $tag$tag$tag\n");
        $tag = $this->gettockens($editor, 'nowiki');
        $html .= $this->makeitem(get_string('wikinowikitext', 'socialwiki'), $tag[0], $tag[1]);
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('div');
        $html .= html_writer::start_tag('button', array('id' => 'styleprops'));
        $html .= $this->imageicon($OUTPUT->pix_url('e/styleprops'));
        $html .= $this->imageicon($OUTPUT->pix_url('t/expanded'));
        $html .= html_writer::end_tag('button');

        $tag = $this->gettockens($editor, 'bold');
        $html .= $this->makebutton($OUTPUT->pix_url('e/bold'), 'wikiboldtext', $tag[0], $tag[1]);

        $tag = $this->gettockens($editor, 'italic');
        $html .= $this->makebutton($OUTPUT->pix_url('e/italic'), 'wikiitalictext', $tag[0], $tag[1]);

        $html .= '</div>';

        // Lists, Break.
        $html .= '<div class="atto_group list_group">';

        $tag = $this->gettockens($editor, 'list');
        $html .= $this->makebutton($OUTPUT->pix_url('e/bullet_list'), 'wikiunorderedlist', "\n$tag[0] ", '', false);
        $html .= $this->makebutton($OUTPUT->pix_url('e/numbered_list'), 'wikiorderedlist', "\n$tag[1] ", '', false);

        $tag = $this->gettockens($editor, 'line_break');
        $html .= $this->makebutton($OUTPUT->pix_url('e/insert_horizontal_ruler'), 'wikihr', "\n$tag\n", '', false);

        $html .= '</div>';

        // Links, Media.
        $html .= '<div class="atto_group links_group">';

        $tag = $this->gettockens($editor, 'link');
        $html .= $this->makebutton($OUTPUT->pix_url('e/insert_edit_link'), 'wikiinternalurl', $tag[0], $tag[1]);

        $tag = $this->gettockens($editor, 'url');
        $html .= $this->makebutton($OUTPUT->pix_url('e/insert_external_link', 'socialwiki'), 'wikiexternalurl', $tag, '');

        $imagetag = $this->gettockens($editor, 'image');
        $html .= $this->makebutton($OUTPUT->pix_url('e/insert_edit_image'), 'wikiimage', $imagetag[0], $imagetag[1]);

        $html .= '</div>';

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

    private function makeitem($title, $starttag, $endtag) {
        return html_writer::tag('li', html_writer::tag('a', $title,
            array('href' => '#', 'start_tag' => $starttag, 'end_tag' => $endtag)));
    }

    private function makebutton($src, $title, $starttag, $endtag, $sampletext = true) {
        return html_writer::tag('button', $this->imageicon($src), array('title' => get_string($title, 'socialwiki'),
            'sample' => $sampletext, 'start_tag' => $starttag, 'end_tag' => $endtag));
    }

    private function imageicon($src) {
        return html_writer::empty_tag('img', array('src' => $src, 'role' => 'presentation',  'aria-hidden' => 'true'));
    }

    private function gettockens($format, $token) {
        $tokens = socialwiki_parser_get_token($format, $token);

        if (is_array($tokens)) {
            foreach ($tokens as &$t) {
                $this->escapetoken($t);
            }
        } else {
            $this->escapetoken($tokens);
        }

        return $tokens;
    }

    private function escapetoken(&$token) {
        $token = urlencode($token);
    }

}

// Register wikieditor.
MoodleQuickForm::registerElementType('socialwikieditor',
    $CFG->dirroot . "/mod/socialwiki/editors/wikieditor.php", 'MoodleQuickForm_socialwikieditor');
