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
 * This file defines a simple editor
 *
 * @author Jordi Piguillem
 * @author Kenneth Riba
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_socialwiki
 *
 */

/**
 * Printing wiki editor.
 * Depending on where it is called , action will go to different destinations.
 * If it is called from comments section, the return will be in comments section
 *  in any other case it will be in edit view section.
 * @param $pageid. Current pageid
 * @param $content. Content to be edited.
 * @param $section. Current section, default null
 * @param $comesfrom. Information about where the function call is made
 * @param commentid. id comment of comment that will be edited.
 */
function socialwiki_print_editor_wiki($pageid, $content, $editor,
        $section = null, $upload = false, $deleteuploads = array(), $comesfrom = 'editorview', $commentid = 0) {
    global $CFG, $OUTPUT, $PAGE;

    if ($comesfrom == 'editcomments') {
        $action = $CFG->wwwroot . '/mod/socialwiki/instancecomments.php?pageid='
                . $pageid . '&id=' . $commentid . '&action=edit';
    } else if ($comesfrom == 'addcomments') {
        $action = $CFG->wwwroot . '/mod/socialwiki/instancecomments.php?pageid='
                . $pageid . '&id=' . $commentid . '&action=add';
    } else {
        $action = $CFG->wwwroot . '/mod/socialwiki/edit.php?pageid=' . $pageid;
    }

    if (!empty($section)) {
        $action .= "&amp;section=" . urlencode($section);
    }

    // Get tags for every element we are displaying.
    $tag = gettockens($editor, 'bold');
    $wikieditor['bold'] = array('ed_bold.gif', get_string('wikiboldtext', 'socialwiki'), $tag[0],
        $tag[1], get_string('wikiboldtext', 'socialwiki'));
    $tag = gettockens($editor, 'italic');
    $wikieditor['italic'] = array('ed_italic.gif', get_string('wikiitalictext', 'socialwiki'),
        $tag[0], $tag[1], get_string('wikiitalictext', 'socialwiki'));
    $tag = gettockens($editor, 'link');
    $wikieditor['internal'] = array('ed_internal.gif', get_string('wikiinternalurl', 'socialwiki'),
        $tag[0], $tag[1], get_string('wikiinternalurl', 'socialwiki'));
    $tag = gettockens($editor, 'url');
    $wikieditor['external'] = array('ed_external.gif', get_string('wikiexternalurl', 'socialwiki'),
        $tag[0], $tag[1], get_string('wikiexternalurl', 'socialwiki'));
    $tag = gettockens($editor, 'list');
    $wikieditor['u_list'] = array('ed_ul.gif', get_string('wikiunorderedlist', 'socialwiki'),
        '\\n' . $tag[0], "", "");
    $wikieditor['o_list'] = array('ed_ol.gif', get_string('wikiorderedlist', 'socialwiki'),
        '\\n' . $tag[1], "", "");
    $tag = gettockens($editor, 'image');
    $wikieditor['image'] = array('ed_img.gif', get_string('wikiimage', 'socialwiki'),
        $tag[0], $tag[1], get_string('wikiimage', 'socialwiki'));
    $tag = gettockens($editor, 'header');
    $wikieditor['h1'] = array('ed_h1.gif', get_string('wikiheader', 'socialwiki', 1), '\\n'
        . $tag . ' ', ' ' . $tag . '\\n', get_string('wikiheader', 'socialwiki', 1));
    $wikieditor['h2'] = array('ed_h2.gif', get_string('wikiheader', 'socialwiki', 2), '\\n'
        . $tag . $tag . ' ', ' ' . $tag . $tag . '\\n', get_string('wikiheader', 'socialwiki', 2));
    $wikieditor['h3'] = array('ed_h3.gif', get_string('wikiheader', 'socialwiki', 3), '\\n'
        . $tag . $tag . $tag . ' ', ' ' . $tag . $tag . $tag . '\\n', get_string('wikiheader', 'socialwiki', 3));
    $tag = gettockens($editor, 'line_break');
    $wikieditor['hr'] = array('ed_hr.gif', get_string('wikihr', 'socialwiki'), '\\n' . $tag . '\\n', "", "");
    $tag = gettockens($editor, 'nowiki');
    $wikieditor['nowiki'] = array('ed_nowiki.gif', get_string('wikinowikitext', 'socialwiki'),
        $tag[0], $tag[1], get_string('wikinowikitext', 'socialwiki'));

    $OUTPUT->heading(strtoupper(get_string('format' . $editor, 'socialwiki')));

    $PAGE->requires->js(new moodle_url('/mod/socialwiki/editors/wiki/buttons.js'));

    echo $OUTPUT->container_start('mdl-align');
    foreach ($wikieditor as $button) {
        echo "<a href=\"javascript:insertTags('$button[2]','$button[3]','$button[4]');\">";
        echo "<img width=\"23\" height=\"22\" src=\"$CFG->wwwroot/mod/socialwiki/editors/wiki/images/"
        . "$button[0]\" alt=\"$button[1]\" title=\"$button[1]\" />";
        echo "</a>";
    }
    echo $OUTPUT->container_end();

    echo $OUTPUT->container_start('mdl-align');
    echo '<form method="post" id="mform1" action="' . $action . '">';
    echo $OUTPUT->container(print_textarea(false, 20, 60, 0, 0, "newcontent", $content, 0, true), false, 'socialwikieditor');
    echo $OUTPUT->container_start();
    socialwiki_print_edit_form_default_fields($editor, $pageid, $upload, $deleteuploads);
    echo $OUTPUT->container_end();
    echo '</form>';
    echo $OUTPUT->container_end();
}

/**
 * Returns escaped token used by a wiki language to represent a given tag or "object" (bold -> **)
 *
 * @param string $format format of page
 * @param array|string $token format tokens which needs to be escaped
 * @return array|string
 */
function gettockens($format, $token) {
    $tokens = socialwiki_parser_get_token($format, $token);

    if (is_array($tokens)) {
        foreach ($tokens as $key => $value) {
            $tokens[$key] = urlencode(str_replace("'", "\'", $value));
        }
    } else {
        urlencode(str_replace("'", "\'", $token));
    }

    return $tokens;
}
