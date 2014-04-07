<?php
/**
 * This file defines a simple editor
 *
 * @author Jordi Piguillem
 * @author Kenneth Riba
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package wiki
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

function socialwiki_print_editor_wiki($pageid, $content, $editor, $version = -1, $section = null, $upload = false, $deleteuploads = array(), $comesfrom = 'editorview', $commentid = 0) {
    global $CFG, $OUTPUT, $PAGE;

    if ($comesfrom == 'editcomments') {
        $action = $CFG->wwwroot . '/mod/socialwiki/instancecomments.php?pageid=' . $pageid . '&id=' . $commentid . '&action=edit';
    } else if ($comesfrom == 'addcomments') {
        $action = $CFG->wwwroot . '/mod/socialwiki/instancecomments.php?pageid=' . $pageid . '&id=' . $commentid . '&action=add';
    } else {
        $action = $CFG->wwwroot . '/mod/socialwiki/edit.php?pageid=' . $pageid;
    }

    if (!empty($section)) {
        $action .= "&amp;section=" . urlencode($section);
    }

    ///Get tags for every element we are displaying
    $tag = getTokens($editor, 'bold');
    $wiki_editor['bold'] = array('ed_bold.gif', get_string('wikiboldtext', 'socialwiki'), $tag[0], $tag[1], get_string('wikiboldtext', 'socialwiki'));
    $tag = getTokens($editor, 'italic');
    $wiki_editor['italic'] = array('ed_italic.gif', get_string('wikiitalictext', 'socialwiki'), $tag[0], $tag[1], get_string('wikiitalictext', 'socialwiki'));
    $tag = getTokens($editor, 'link');
    $wiki_editor['internal'] = array('ed_internal.gif', get_string('wikiinternalurl', 'socialwiki'), $tag[0], $tag[1], get_string('wikiinternalurl', 'socialwiki'));
    $tag = getTokens($editor, 'url');
    $wiki_editor['external'] = array('ed_external.gif', get_string('wikiexternalurl', 'socialwiki'), $tag[0], $tag[1], get_string('wikiexternalurl', 'socialwiki'));
    $tag = getTokens($editor, 'list');
    $wiki_editor['u_list'] = array('ed_ul.gif', get_string('wikiunorderedlist', 'socialwiki'), '\\n' . $tag[0], '', '');
    $wiki_editor['o_list'] = array('ed_ol.gif', get_string('wikiorderedlist', 'socialwiki'), '\\n' . $tag[1], '', '');
    $tag = getTokens($editor, 'image');
    $wiki_editor['image'] = array('ed_img.gif', get_string('wikiimage', 'socialwiki'), $tag[0], $tag[1], get_string('wikiimage', 'socialwiki'));
    $tag = getTokens($editor, 'header');
    $wiki_editor['h1'] = array('ed_h1.gif', get_string('wikiheader', 'socialwiki', 1), '\\n' . $tag . ' ', ' ' . $tag . '\\n', get_string('wikiheader', 'socialwiki', 1));
    $wiki_editor['h2'] = array('ed_h2.gif', get_string('wikiheader', 'socialwiki', 2), '\\n' . $tag . $tag . ' ', ' ' . $tag . $tag . '\\n', get_string('wikiheader', 'socialwiki', 2));
    $wiki_editor['h3'] = array('ed_h3.gif', get_string('wikiheader', 'socialwiki', 3), '\\n' . $tag . $tag . $tag . ' ', ' ' . $tag . $tag . $tag . '\\n', get_string('wikiheader', 'socialwiki', 3));
    $tag = getTokens($editor, 'line_break');
    $wiki_editor['hr'] = array('ed_hr.gif', get_string('wikihr', 'socialwiki'), '\\n' . $tag . '\\n', '', '');
    $tag = getTokens($editor, 'nowiki');
    $wiki_editor['nowiki'] = array('ed_nowiki.gif', get_string('wikinowikitext', 'socialwiki'), $tag[0], $tag[1], get_string('wikinowikitext', 'socialwiki'));

    $OUTPUT->heading(strtoupper(get_string('format' . $editor, 'socialwiki')));

    $PAGE->requires->js($CFG->wwwroot .'/mod/socialwiki/editors/wiki/buttons.js');

    echo $OUTPUT->container_start('mdl-align');
    foreach ($wiki_editor as $button) {
        echo "<a href=\"javascript:insertTags";
        echo "('" . $button[2] . "','" . $button[3] . "','" . $button[4] . "');\">";
        echo "<img width=\"23\" height=\"22\" src=\"$CFG->wwwroot/mod/socialwiki/editors/wiki/images/$button[0]\" alt=\"" . $button[1] . "\" title=\"" . $button[1] . "\" />";
        echo "</a>";
    }
    echo $OUTPUT->container_end();

    echo $OUTPUT->container_start('mdl-align');
    echo '<form method="post" id="mform1" action="' . $action . '">';
    echo $OUTPUT->container(print_textarea(false, 20, 60, 0, 0, "newcontent", $content, 0, true), false, 'socialwiki_editor');
    echo $OUTPUT->container_start();
    socialwiki_print_edit_form_default_fields($editor, $pageid, $version, $upload, $deleteuploads);
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
function getTokens($format, $token) {
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
