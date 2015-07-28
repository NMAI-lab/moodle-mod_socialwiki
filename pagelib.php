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
 * This file contains several classes uses to render the different pages of the socialwiki module.
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

/**
 * The standard overriden socialwiki page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class page_socialwiki {

    /**
     * Current subwiki.
     *
     * @var stdClass
     */
    protected $subwiki;

    /**
     * Current wiki.
     *
     * @var stdClass
     */
    protected $wiki;

    /**
     * Current page.
     *
     * @var int
     */
    protected $page;

    /**
     * Current page title.
     *
     * @var string
     */
    protected $title;

    /**
     * Current group ID.
     *
     * @var int
     */
    protected $gid;

    /**
     * Module context object.
     *
     * @var stdClass
     */
    protected $modcontext;

    /**
     * Current user ID.
     *
     * @var int
     */
    protected $uid;

    /**
     * The tabs set used in social wiki module.
     * Refers to terms listed in file socialwiki.php under lang/en folder.
     *
     * @var array
     */
    protected $tabs = array('view' => 'view', 'edit' => 'edit', 'history' => 'versions', 'admin' => 'admin');
    // All tabs are: array('home'=>'home', 'view' => 'view', 'edit' => 'edit', 'comments' => 'comments',
    // 'history' => 'versions', 'admin' => 'admin').

    /**
     * The tab options.
     *
     * @var array
     */
    protected $taboptions = array();

    /**
     * Wiki renderer.
     *
     * @var stdClass
     */
    protected $wikioutput;

    /**
     * The CSS style.
     *
     * @var string
     */
    protected $style;

    /**
     * Makes a form to chose the combine method for peer data.
     *
     * @return string HTML
     */
    public static function get_combine_form() {
        return '<form class="combineform" action="">'
            . 'For each page version show: <select class="combiner">'
            . '<option value="max" selected="selected">max</option>'
            . '<option value="min">min</option><option value="avg">avg</option>'
            . '<option value="sum">sum</option></select> of trust indicator values.</form>';
    }

    /**
     * Creates the standard socialwiki page.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $cm The current course module.
     */
    public function __construct($wiki, $subwiki, $cm) {
        global $PAGE, $USER;
        $this->subwiki = $subwiki;
        $this->wiki = $wiki;
        $this->modcontext = context_module::instance($PAGE->cm->id);
        // Initialise wiki renderer.
        $this->wikioutput = $PAGE->get_renderer('mod_socialwiki');
        $PAGE->set_cacheable(true);
        $PAGE->set_cm($cm);
        $PAGE->set_activity_record($wiki);
        $PAGE->requires->jquery();
        $this->style = socialwiki_get_currentstyle($wiki->id);
        $PAGE->requires->css(new moodle_url("/mod/socialwiki/{$this->style->style}_style.css"));
        // The search box.
        $PAGE->set_button(socialwiki_search_form($cm));
        $this->set_uid($USER->id);
    }

    /**
     * Prints out the header at the top of the page.
     */
    public function print_header() {
        global $OUTPUT, $PAGE;
        $PAGE->set_heading(format_string($PAGE->course->fullname));
        $this->set_url();
        $this->set_session_url();

        $this->create_navbar();
        echo $OUTPUT->header();
        echo $this->wikioutput->content_area_begin();

        $this->print_pagetitle();
        $this->setup_tabs();
        // Tabs are associated with pageid, so if page is empty, tabs should be disabled.
        if (!empty($this->page) && !empty($this->tabs)) {
            $tabthing = $this->wikioutput->tabs($this->page, $this->tabs, $this->taboptions); // Calls tabs function in renderer.
            echo $tabthing;
        }
    }

    /**
     * Sets the URL of the page.
     * This method must be overwritten by every type of page.
     */
    protected function set_url() {
        throw new coding_exception('Page socialwiki class does not implement method set_url()');
    }

    /**
     * Sets the session url for the current session.
     */
    protected function set_session_url() {
        global $SESSION;
        unset($SESSION->wikipreviousurl);
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE, $CFG;
        $PAGE->navbar->add(format_string($this->title), $CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $this->page->id);
    }

    /**
     * Prints the page title.
     */
    protected function print_pagetitle() {
        global $OUTPUT;
        $html = '';
        $html .= $OUTPUT->container_start();
        $html .= $OUTPUT->heading(format_string($this->title), 1, 'socialwiki-title');
        $html .= $OUTPUT->container_end();
        echo $html;
    }

    /**
     * Setup page tabs, if options is empty, will set up active tab automatically.
     *
     * @param array $options The tab options.
     */
    protected function setup_tabs($options = array()) {
        global $PAGE;
        $groupmode = groups_get_activity_groupmode($PAGE->cm);

        if (!has_capability('mod/socialwiki:editpage', $PAGE->context)) {
            unset($this->tabs['edit']);
        }

        if ($groupmode and $groupmode == VISIBLEGROUPS) {
            $currentgroup = groups_get_activity_group($PAGE->cm);
            $manage = has_capability('mod/socialwiki:managewiki', $PAGE->cm->context);
            $edit = has_capability('mod/socialwiki:editpage', $PAGE->context);
            if (!$manage and ! ($edit and groups_is_member($currentgroup))) {
                unset($this->tabs['edit']);
            }
        }

        if (empty($options)) {
            $this->taboptions = array('activetab' => substr(get_class($this), 16));
        } else {
            $this->taboptions = $options;
        }
    }

    /**
     * Prints the page content.
     * This method must be overwritten to print the page content.
     */
    public function print_content() {
        throw new coding_exception('Page socialwiki class does not implement method print_content()');
    }

    /**
     * Method to set the current page.
     *
     * @param stdClass $page Current page.
     */
    public function set_page($page) {
        global $PAGE;
        $this->page = $page;
        $this->title = $page->title;
        $PAGE->set_title($this->title);
    }

    /**
     * Sets the current page title.
     * This method must be called when the current page is not created yet.
     *
     * @param string $title Current page title.
     */
    public function set_title($title) {
        global $PAGE;
        $this->page = null;
        $this->title = $title;
        $PAGE->set_title($this->title);
    }

    /**
     * Sets current group ID.
     *
     * @param int $gid Current group ID.
     */
    public function set_gid($gid) {
        $this->gid = $gid;
    }

    /**
     * Sets current user ID.
     *
     * @param int $uid Current user ID.
     */
    public function set_uid($uid) {
        $this->uid = $uid;
    }

    /**
     * Prints out the footer at the bottom of the page.
     */
    public function print_footer() {
        global $OUTPUT;
        echo $this->wikioutput->content_area_end();
        echo $OUTPUT->footer();
    }
}

/**
 * The socialwiki view page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_view extends page_socialwiki {

    /**
     * The course module id.
     *
     * @var int
     */
    private $coursemodule;

    /**
     * Prints out the header at the top of the page.
     */
    public function print_header() {
        global $PAGE;
        parent::print_header();
        // JS code for the ajax-powered like button.
        $PAGE->requires->js(new moodle_url("/mod/socialwiki/likeajax.js"));
        $this->wikioutput->socialwiki_print_subwiki_selector($PAGE->activityrecord, $this->subwiki, $this->page, 'view');
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $params = array();

        if (isset($this->coursemodule)) {
            $params['id'] = $this->coursemodule;
        } else if (!empty($this->page) and $this->page != null) {
            $params['pageid'] = $this->page->id;
        } else if (!empty($this->gid)) {
            $params['wid'] = $PAGE->cm->instance;
            $params['group'] = $this->gid;
        } else if (!empty($this->title)) {
            $params['swid'] = $this->subwiki->id;
            $params['title'] = $this->title;
        } else {
            print_error(get_string('invalidparameters', 'socialwiki'));
        }

        $PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/socialwiki/view.php', $params));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;
        $PAGE->navbar->add(format_string($this->title));
        $PAGE->navbar->add(get_string('view', 'socialwiki'));
    }

    /**
     * Prints the page title.
     */
    protected function print_pagetitle() {
        global $OUTPUT;
        $html = '';
        $html .= $OUTPUT->container_start('', 'socialwiki-title');
        $html .= '<script> var options="?pageid='.$this->page->id.'&sesskey='.sesskey().'"</script>'; // Passed to likeajax.js.

        $thetitle = html_writer::start_tag('h1');
        $thetitle .= format_string($this->page->title);
        $thetitle .= html_writer::end_tag('h1');

        $isliked = socialwiki_liked($this->uid, $this->page->id);
        $likecurrent = ($isliked ? 'unlike' : 'like');
        $likeother = (!$isliked ? 'unlike' : 'like');
        $pixurl = new moodle_url('/mod/socialwiki/pix/icons/');

        $theliker = '<noscript>' . html_writer::start_tag('form',
                array('style' => "display: inline", 'action' => 'like.php', "method" => "get"));
        $theliker .= '<input type="hidden" name="pageid" value="' . $this->page->id . '"/>';
        $theliker .= '<input type="hidden" name="sesskey" value="' . sesskey() . '" />' . '</noscript>';
        $theliker .= html_writer::start_tag('div', array('style' => 'float:right'));

        $theliker .= html_writer::start_tag('button',
                array('class' => 'socialwiki-likebutton', 'title' => get_string('like_tip', 'socialwiki')));
        $theliker .= html_writer::tag('img', '', array('src' => $pixurl.$likecurrent.'.png', 'other' => $pixurl.$likeother.'.png'));
        $theliker .= '<span other='.get_string($likeother, 'socialwiki').'>'.get_string($likecurrent, 'socialwiki').'</span>';
        $theliker .= html_writer::end_tag('button');

        // Show number of likes.
        $numlikes = socialwiki_numlikes($this->page->id);
        $theliker .= html_writer::start_tag('div', array('id' => 'numlikes'));
        $theliker .= $numlikes . ($numlikes == 1 ? ' like' : ' likes');
        $theliker .= html_writer::end_tag('div');

        $theliker .= html_writer::end_tag('div');
        $theliker .= '<noscript>' . html_writer::end_tag('form') . '</noscript>';

        $html .= $theliker . $thetitle . $OUTPUT->container_end();
        echo $html;
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        if (socialwiki_user_can_view($this->subwiki)) {
            if (!empty($this->page)) {
                socialwiki_print_page_content($this->page, $this->modcontext, $this->subwiki->id);
                echo $this->wikioutput->prettyview_link($this->page);
            } else {
                echo get_string('nocontent', 'socialwiki');
            }
        } else {
            echo get_string('cannotviewpage', 'socialwiki');
        }
    }

    /**
     * Sets the current course module.
     *
     * @param int $id
     */
    public function set_coursemodule($id) {
        $this->coursemodule = $id;
    }
}

/**
 * The socialwiki edit page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_edit extends page_socialwiki {

    /**
     * The options available for attachments.
     *
     * @var array
     */
    public static $attachmentoptions;

    /**
     * The section name.
     *
     * @var string
     */
    protected $section;

    /**
     * The section content.
     *
     * @var string
     */
    protected $sectioncontent;

    protected $upload = false;
    protected $attachments = 0;
    protected $deleteuploads = array();
    protected $format;
    protected $makenew;

    /**
     * Creates a new edit page.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $cm The current course module.
     * @param bool $makenew Whether to make a new topic from this page (no parent).
     */
    public function __construct($wiki, $subwiki, $cm, $makenew) {
        global $CFG;
        parent::__construct($wiki, $subwiki, $cm);
        $this->makenew = $makenew;
        self::$attachmentoptions = array('subdirs' => false,
            'maxfiles' => - 1, 'maxbytes' => $CFG->maxbytes, 'accepted_types' => '*');
    }

    /**
     * Sets the URL of the page.
     */
    protected function set_url() {
        global $PAGE, $CFG;

        $params = array('pageid' => $this->page->id);

        if (isset($this->section)) {
            $params['section'] = $this->section;
        }
        $params['makenew'] = $this->makenew;
        $PAGE->set_url("$CFG->wwwroot/mod/socialwiki/edit.php?makenew=$this->makenew", $params);
    }

    /**
     * Sets the session URL of the page.
     */
    protected function set_session_url() {
        global $SESSION;

        $SESSION->wikipreviousurl = array('page' => 'edit',
            'params' => array('pageid' => $this->page->id, 'section' => $this->section));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;
        parent::create_navbar();
        $PAGE->navbar->add(get_string('edit', 'socialwiki'));
    }

    /**
     * Prints out the header at the top of the page.
     */
    protected function print_pagetitle() {
        global $OUTPUT;

        $title = $this->page->title;
        if (isset($this->section)) {
            $title .= ' : ' . $this->section;
        }
        echo $OUTPUT->heading(format_string($title), 1, 'socialwiki-title');
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        if (socialwiki_user_can_edit($this->subwiki)) {
            $this->print_edit();
        } else {
            echo get_string('cannoteditpage', 'socialwiki');
        }
    }

    /**
     * Sets section information.
     *
     * @param string $sectioncontent The section content.
     * @param string $section The section name.
     */
    public function set_section($sectioncontent, $section) {
        $this->sectioncontent = $sectioncontent;
        $this->section = $section;
    }

    /**
     * Sets the page formatting style.
     *
     * @param string $format The formatting style.
     */
    public function set_format($format) {
        $this->format = $format;
    }

    public function set_upload($upload) {
        $this->upload = $upload;
    }

    public function set_attachments($attachments) {
        $this->attachments = $attachments;
    }

    public function set_deleteuploads($deleteuploads) {
        $this->deleteuploads = $deleteuploads;
    }

    /**
     * Prints the editing content pane.
     *
     * @param string $content The current content from the previous version.
     */
    protected function print_edit($content = null) {
        global $CFG;

        $format = $this->page->format;

        if ($content == null) {
            if (empty($this->section)) {
                $content = $this->page->content;
            } else {
                $content = $this->sectioncontent;
            }
        }

        $url = $CFG->wwwroot . '/mod/socialwiki/edit.php?pageid=' . $this->page->id . '&makenew=' . $this->makenew;
        if (!empty($this->section)) {
            $url .= "&section=" . urlencode($this->section);
        }

        $params = array(
            'attachmentoptions' => self::$attachmentoptions,
            'format' => $this->page->format,
            'pagetitle' => $this->page->title,
            'contextid' => $this->modcontext->id
        );

        $data = new stdClass();
        $data->newcontent = $content;
        $data->format = $format;

        switch ($format) {
            case 'html':
                $data->newcontentformat = FORMAT_HTML;
                // Append editor context to editor options, giving preference to existing context.
                self::$attachmentoptions = array_merge(
                        array('context' => $this->modcontext), self::$attachmentoptions);
                $data = file_prepare_standard_editor($data, 'newcontent', self::$attachmentoptions,
                        $this->modcontext, 'mod_socialwiki', 'attachments', $this->subwiki->id);
                break;
            default:
                break;
        }

        if ($this->page->format != 'html') {
            $params['fileitemid'] = $this->subwiki->id;
            $params['component'] = 'mod_socialwiki';
            $params['filearea'] = 'attachments';
        }
        $form = new mod_socialwiki_edit_form($url, $params);
        $form->set_data($data);
        $form->display();
    }
}

/**
 * The socialwiki comments page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_comments extends page_socialwiki {

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/comments.php', array('pageid' => $this->page->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;
        parent::create_navbar();
        $PAGE->navbar->add(get_string('comments', 'socialwiki'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $CFG, $OUTPUT, $USER;
        require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
        list($context, $course, $cm) = get_context_info_array($this->modcontext->id);

        require_capability('mod/socialwiki:viewcomment', $this->modcontext, null, true, 'noviewcommentpermission', 'socialwiki');

        $comments = socialwiki_get_comments($this->modcontext->id, $this->page->id);

        if (has_capability('mod/socialwiki:editcomment', $this->modcontext)) {
            echo "<div class='midpad'><a href='$CFG->wwwroot/mod/socialwiki/editcomments.php?action=add&amp;pageid="
                    . "{$this->page->id}'>" . get_string('addcomment', 'socialwiki') . "</a></div>";
        }

        $options = array('swid' => $this->page->subwikiid, 'pageid' => $this->page->id);
        $format = $this->page->format;

        if (empty($comments)) {
            echo $OUTPUT->heading(get_string('nocomments', 'socialwiki'));
        }

        foreach ($comments as $comment) {

            $user = socialwiki_get_user_info($comment->userid);

            $fullname = fullname($user, has_capability('moodle/site:viewfullnames', context_course::instance($course->id)));
            $by = new stdclass();
            $by->name = '<a href="' . $CFG->wwwroot . '/mod/socialwiki/viewuserpages.php?userid='
                    . $user->id . '&amp;subwikiid=' . $this->page->subwikiid . '">' . $fullname . '</a>';
            $by->date = userdate($comment->timecreated);

            $t = new html_table();
            $cell1 = new html_table_cell($OUTPUT->user_picture($user, array('popup' => true)));
            $cell2 = new html_table_cell(get_string('bynameondate', 'forum', $by));
            $cell3 = new html_table_cell();
            $cell3->atributtes ['width'] = "80%";
            $cell4 = new html_table_cell();
            $cell5 = new html_table_cell();

            $row1 = new html_table_row();
            $row1->cells[] = $cell1;
            $row1->cells[] = $cell2;
            $row2 = new html_table_row();
            $row2->cells[] = $cell3;

            if ($format != 'html') {
                if ($format == 'creole') {
                    $parsedcontent = socialwiki_parse_content('creole', $comment->content, $options);
                } else if ($format == 'nwiki') {
                    $parsedcontent = socialwiki_parse_content('nwiki', $comment->content, $options);
                }

                $cell4->text = format_text(html_entity_decode($parsedcontent['parsed_text'], ENT_QUOTES, 'UTF-8'), FORMAT_HTML);
            } else {
                $cell4->text = format_text($comment->content, FORMAT_HTML);
            }

            $row2->cells[] = $cell4;

            $t->data = array($row1, $row2);

            $actionicons = false;
            if ((has_capability('mod/socialwiki:managecomment', $this->modcontext))) {
                $urledit = new moodle_url('/mod/socialwiki/editcomments.php',
                        array('commentid' => $comment->id, 'pageid' => $this->page->id, 'action' => 'edit'));
                $urldelet = new moodle_url('/mod/socialwiki/instancecomments.php',
                        array('commentid' => $comment->id, 'pageid' => $this->page->id, 'action' => 'delete'));
                $actionicons = true;
            } else if ((has_capability('mod/socialwiki:editcomment', $this->modcontext)) and ( $USER->id == $user->id)) {
                $urledit = new moodle_url('/mod/socialwiki/editcomments.php',
                        array('commentid' => $comment->id, 'pageid' => $this->page->id, 'action' => 'edit'));
                $urldelet = new moodle_url('/mod/socialwiki/instancecomments.php',
                        array('commentid' => $comment->id, 'pageid' => $this->page->id, 'action' => 'delete'));
                $actionicons = true;
            }

            if ($actionicons) {
                $cell6 = new html_table_cell($OUTPUT->action_icon($urledit, new pix_icon('t/edit', get_string('edit'), '',
                        array('class' => 'iconsmall'))) . $OUTPUT->action_icon($urldelet,
                                new pix_icon('t/delete', get_string('delete'), '', array('class' => 'iconsmall'))));
                $row3 = new html_table_row();
                $row3->cells[] = $cell5;
                $row3->cells[] = $cell6;
                $t->data[] = $row3;
            }

            echo html_writer::tag('div', html_writer::table($t), array('class' => 'no-overflow'));
        }
    }
}

/**
 * The socialwiki edit comment page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_editcomment extends page_socialwiki {

    private $comment;
    private $action;
    private $form;
    private $format;

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/comments.php', array('pageid' => $this->page->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE, $CFG;

        $PAGE->navbar->add(get_string('comments', 'socialwiki'), $CFG->wwwroot
                . '/mod/socialwiki/comments.php?pageid=' . $this->page->id);

        if ($this->action == 'add') {
            $PAGE->navbar->add(get_string('insertcomment', 'socialwiki'));
        } else {
            $PAGE->navbar->add(get_string('editcomment', 'socialwiki'));
        }
    }

    /**
     * Setup page tabs.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'comments', 'activetab' => 'comments'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        require_capability('mod/socialwiki:editcomment', $this->modcontext, null, true, 'noeditcommentpermission', 'socialwiki');

        if ($this->action == 'add') {
            $this->add_comment_form();
        } else if ($this->action == 'edit') {
            $this->edit_comment_form($this->comment);
        }
    }

    public function set_action($action, $comment) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/socialwiki/comments_form.php');

        $this->action = $action;
        $this->comment = $comment;
        $this->format = $this->page->format;

        if ($this->format == 'html') {
            $destination = $CFG->wwwroot . '/mod/socialwiki/instancecomments.php?pageid=' . $this->page->id;
            $this->form = new mod_socialwiki_comments_form($destination);
        }
    }

    private function add_comment_form() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/socialwiki/editors/socialwiki_editor.php');

        if ($this->format == 'html') {
            $com = new stdClass();
            $com->action = 'add';
            $com->commentoptions = array('trusttext' => true, 'maxfiles' => 0);
            $this->form->set_data($com);
            $this->form->display();
        } else {
            socialwiki_print_editor_wiki($this->page->id, null, $this->format, null, false, null, 'addcomments');
        }
    }

    private function edit_comment_form($com) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/socialwiki/comments_form.php');
        require_once($CFG->dirroot . '/mod/socialwiki/editors/socialwiki_editor.php');

        if ($this->format == 'html') {
            $com->action = 'edit';
            $com->entrycomment_editor['text'] = $com->content;
            $com->commentoptions = array('trusttext' => true, 'maxfiles' => 0);

            $this->form->set_data($com);
            $this->form->display();
        } else {
            socialwiki_print_editor_wiki($this->page->id,
                    $com->content, $this->format, null, false, array(), 'editcomments', $com->id);
        }
    }

}

/**
 * The socialwiki search page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_search extends page_socialwiki {

    /**
     * Array of search result pages.
     *
     * @var stdClass[]
     */
    private $searchresult;

    /**
     * The search string.
     *
     * @var string
     */
    private $searchstring;

    /**
     * The view mode for viewing results.
     *
     * @var int
     */
    private $view;

    /**
     * 1 for an exact search type.
     *
     * @var int
     */
    private $exact;

    public function __construct($wiki, $subwiki, $cm, $option) {
        global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        $this->view = $option;
        if ($this->view == 2) {
            // For table view.
            $PAGE->requires->js(new moodle_url("table/jquery.dataTables.js"));
            $PAGE->requires->js(new moodle_url("/mod/socialwiki/table/table.js"));
            $PAGE->requires->css(new moodle_url("/mod/socialwiki/table/table.css"));
        } else {
            // For tree view.
            $PAGE->requires->js(new moodle_url("/mod/socialwiki/search.js"));
            $PAGE->requires->js(new moodle_url("/mod/socialwiki/tree/tree.js"));
            $PAGE->requires->css(new moodle_url("/mod/socialwiki/tree/tree.css"));
        }
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG, $COURSE;
        if (isset($this->page)) {
            $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/search.php?pageid='
                    . $this->page->id . '&courseid=' . $COURSE->id . '&cmid=' . $PAGE->cm->id);
        } else {
            $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/search.php');
        }
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;
        $PAGE->navbar->add(format_string($this->title));
    }

    /**
     * Sets all search data.
     *
     * @param string $search The string that is searched.
     * @param bool $searchcontent Whether to search the page content as well.
     * @param int $exactmatch An exact match will only return pages with the exact title.
     */
    public function set_search_string($search, $searchcontent, $exactmatch = false) {
        $swid = $this->subwiki->id;
        $this->searchstring = $search;
        $this->exact = $exactmatch;
        if ($searchcontent) {
            $this->searchresult = socialwiki_search_all($swid, $search);
        } else {
            $this->searchresult = socialwiki_search_title($swid, $search, $exactmatch);
        }
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $PAGE;
        require_capability('mod/socialwiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');
        echo $this->wikioutput->menu_search($PAGE->cm->id, $this->view, $this->searchstring, $this->exact);
        if ($this->view == 2) {
            $this->print_table();
        } else {
            $this->print_tree();
        }
    }

    /**
     * Print the table view.
     */
    private function print_table() {
        global $USER, $CFG;
        require($CFG->dirroot . '/mod/socialwiki/table/table.php');
        $pages = $this->searchresult;
        echo socialwiki_versiontable::html_versiontable($USER->id, $this->subwiki->id, $pages, 'version');
    }

    /**
     * Print the tree view.
     */
    private function print_tree() {
        global $CFG;
        require($CFG->dirroot . '/mod/socialwiki/tree/tree.php');
        $pages = $this->searchresult;
        $tree = new socialwiki_tree;
        $tree->build_tree($pages);
        $tree->display();
    }
}

/**
 * The socialwiki create page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_create extends page_socialwiki {

    private $format;
    private $swid;
    private $wid;
    private $action;
    private $mform;
    private $groups;

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE;

        $params = array();
        $params['swid'] = $this->swid;
        if ($this->action == 'new') {
            $params['action'] = 'new';
            $params['wid'] = $this->wid;
            if ($this->title != get_string('newpage', 'socialwiki')) {
                $params['title'] = $this->title;
            }
        } else {
            $params['action'] = 'create';
        }
        $PAGE->set_url(new moodle_url('/mod/socialwiki/edit.php', $params));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;
        // The navigation_node::get_content formats this before printing.
        $PAGE->navbar->add($this->title);
    }

    public function set_format($format) {
        $this->format = $format;
    }

    public function set_wid($wid) {
        $this->wid = $wid;
    }

    public function set_swid($swid) {
        $this->swid = $swid;
    }

    public function set_availablegroups($group) {
        $this->groups = $group;
    }

    public function set_action($action) {
        global $PAGE;
        $this->action = $action;

        $url = new moodle_url('/mod/socialwiki/create.php',
                array('action' => 'create', 'wid' => $PAGE->activityrecord->id, 'group' => $this->gid, 'uid' => $this->uid));
        $formats = socialwiki_get_formats();
        $options = array('formats' => $formats, 'defaultformat' => $PAGE->activityrecord->defaultformat,
            'forceformat' => $PAGE->activityrecord->forceformat, 'groups' => $this->groups);
        if ($this->title != get_string('newpage', 'socialwiki')) {
            $options['disable_pagetitle'] = true;
        }
        $this->mform = new mod_socialwiki_create_form($url->out(false), $options);
    }

    /**
     * Prints the page content.
     *
     * @param string $pagetitle The page title.
     */
    public function print_content($pagetitle = '') {
        global $PAGE;

        // TODO: Change this to has_capability and show an alternative interface.
        require_capability('mod/socialwiki:createpage', $this->modcontext, null, true, 'nocreatepermission', 'socialwiki');
        $data = new stdClass();
        if (!empty($pagetitle)) {
            $data->pagetitle = $pagetitle;
        }
        $data->pageformat = $PAGE->activityrecord->defaultformat;

        $this->mform->set_data($data);
        $this->mform->display();
    }

    public function create_page($pagetitle) {
        global $USER, $PAGE;

        $data = $this->mform->get_data();
        if (isset($data->groupinfo)) {
            $groupid = $data->groupinfo;
        } else if (!empty($this->gid)) {
            $groupid = $this->gid;
        } else {
            $groupid = '0';
        }
        if (empty($this->subwiki)) {
            // If subwiki is not set then try find one and set else create one.
            if (!$this->subwiki = socialwiki_get_subwiki_by_group($this->wid, $groupid, $this->uid)) {
                $swid = socialwiki_add_subwiki($PAGE->activityrecord->id, $groupid, $this->uid);
                $this->subwiki = socialwiki_get_subwiki($swid);
            }
        }
        if ($data) {
            $this->set_title($data->pagetitle);
            $id = socialwiki_create_page($this->subwiki->id, $data->pagetitle, $data->pageformat, $USER->id);
        } else {
            $this->set_title($pagetitle);
            $id = socialwiki_create_page($this->subwiki->id, $pagetitle, $PAGE->activityrecord->defaultformat, $USER->id);
        }
        $this->page = $id;
        return $id;
    }
}

/**
 * The socialwiki preview page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_preview extends page_socialwiki_edit {

    private $newcontent;

    /**
     * Creates a new preview page.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $cm The current course module.
     */
    public function __construct($wiki, $subwiki, $cm) {
        global $PAGE, $OUTPUT;
        parent::__construct($wiki, $subwiki, $cm, 0);
        $buttons = $OUTPUT->update_module_button($cm->id, 'socialwiki');
        $PAGE->set_button($buttons);
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;

        $params = array('pageid' => $this->page->id);
        if (isset($this->section)) {
            $params['section'] = $this->section;
        }

        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/edit.php', $params);
    }

    /**
     * Setup page tabs.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'view', 'activetab' => 'view'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        require_capability('mod/socialwiki:editpage', $this->modcontext, null, true, 'noeditpermission', 'socialwiki');
        $this->print_preview();
    }

    public function set_newcontent($newcontent) {
        $this->newcontent = $newcontent;
    }

    protected function print_preview() {
        global $CFG, $OUTPUT;

        $content = $this->page->content;

        $url = $CFG->wwwroot . '/mod/socialwiki/edit.php?pageid=' . $this->page->id;
        if (!empty($this->section)) {
            $url .= "&section=" . urlencode($this->section);
        }
        $params = array(
            'attachmentoptions' => page_socialwiki_edit::$attachmentoptions,
            'format' => $this->format,
            'contextid' => $this->modcontext->id
        );

        if ($this->format != 'html') {
            $params['component'] = 'mod_socialwiki';
            $params['filearea'] = 'attachments';
            $params['fileitemid'] = $this->page->id;
        }
        $form = new mod_socialwiki_edit_form($url, $params);

        $options = array('swid' => $this->page->subwikiid, 'pageid' => $this->page->id, 'pretty_print' => true);

        if ($data = $form->get_data()) {
            if (isset($data->newcontent)) {
                // Wiki format.
                $text = $data->newcontent;
            } else {
                // HTML format.
                $text = $data->newcontent_editor['text'];
            }
            $parseroutput = socialwiki_parse_content($data->contentformat, $text, $options);
            $this->set_newcontent($text);
            echo $OUTPUT->notification(get_string('previewwarning', 'socialwiki'), 'notifyproblem socialwiki-info');
            $content = format_text($parseroutput['parsed_text'], FORMAT_HTML, array('overflowdiv' => true, 'filter' => false));
            echo $OUTPUT->box($content, 'generalbox socialwiki-previewbox');
            $content = $this->newcontent;
        }

        $this->print_edit($content);
    }
}

/**
 * The socialwiki difference page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_diff extends page_socialwiki {

    private $compare;
    private $comparewith;

    /**
     * Prints out the header at the top of the page.
     */
    public function print_header() {
        parent::print_header();

        $vstring = new stdClass();
        $vstring->old = $this->compare;
        $vstring->new = $this->comparewith;
        echo get_string('comparewith', 'socialwiki', $vstring);
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;

        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/diff.php',
                array('pageid' => $this->page->id, 'comparewith' => $this->comparewith, 'compare' => $this->compare));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('history', 'socialwiki'), $CFG->wwwroot
                . '/mod/socialwiki/history.php?pageid=' . $this->page->id);
        $PAGE->navbar->add(get_string('diff', 'socialwiki'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        require_capability('mod/socialwiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');

        $this->print_diff_content();
    }

    public function set_comparison($compare, $comparewith) {
        $this->compare = $compare;
        $this->comparewith = $comparewith;
    }

    /**
     * Given two pages, prints a page displaying the differences between them.
     */
    private function print_diff_content() {
        $pageid = $this->page->id;

        $oldversion = socialwiki_get_page($this->compare);

        $newversion = socialwiki_get_page($this->comparewith);

        if ($oldversion && $newversion) {
            $oldtext = format_text(file_rewrite_pluginfile_urls($oldversion->content,
                    'pluginfile.php', $this->modcontext->id, 'mod_socialwiki', 'attachments', $this->subwiki->id));
            $newtext = format_text(file_rewrite_pluginfile_urls($newversion->content,
                    'pluginfile.php', $this->modcontext->id, 'mod_socialwiki', 'attachments', $this->subwiki->id));
            list($diff1, $diff2) = ouwiki_diff_html($oldtext, $newtext);
            $oldversion->diff = $diff1;
            $oldversion->user = socialwiki_get_user_info($oldversion->userid);
            $newversion->diff = $diff2;
            $newversion->user = socialwiki_get_user_info($newversion->userid);

            echo $this->wikioutput->diff($pageid, $oldversion, $newversion);
        } else {
            print_error('versionerror', 'socialwiki');
        }
    }
}

/**
 * The socialwiki history (version) page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_history extends page_socialwiki {

    /**
     * If != 0, all versions will be printed in a signle table.
     *
     * @var int
     */
    private $allversion;

    /**
     * Creates a new history page.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $cm The current course module.
     */
    public function __construct($wiki, $subwiki, $cm) {
        global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        $PAGE->requires->js(new moodle_url("/mod/socialwiki/tree/tree.js"));
        $PAGE->requires->css(new moodle_url("/mod/socialwiki/tree/tree.css"));
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/history.php', array('pageid' => $this->page->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('history', 'socialwiki'));
    }

    /**
     * Setup page tabs.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'versions', 'activetab' => 'versions'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $OUTPUT;

        require_capability('mod/socialwiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');
        $history = socialwiki_get_relations($this->page->id);

        // Build the tree with all of the relate pages.
        $tree = new socialwiki_tree();
        $tree->build_tree($history);
        // Add radio buttons to compare versions if there is more than one version.
        if (count($tree->nodes) > 1) {
            foreach ($tree->nodes as $node) {
                $node->content .= '<span id="comp' . $node->id . '" style="display:block">';
                $node->content .= $this->choose_from_radio(array(substr($node->id, 1) => null), 'compare')
                        . $this->choose_from_radio(array(substr($node->id, 1) => null), 'comparewith');
                if ($node->id == 'l' . $this->page->id) { // Current page.
                    $node->content .= "<br/>[current page]";
                }
                $node->content .= "</span>";
            }
        }

        echo html_writer::start_tag('form', array('action' => new moodle_url('/mod/socialwiki/diff.php'),
                                                  'method' => 'get', 'id' => 'diff'));
        echo html_writer::tag('div', html_writer::empty_tag('input', array('type' => 'hidden',
                                                                           'name' => 'pageid', 'value' => $this->page->id)));

        $tree->display();
        // Add compare button only if there are multiple versions of a page.
        if (count($tree->nodes) > 1) {
            echo $OUTPUT->container_start('socialwiki-diffbutton');
            echo html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'socialwiki-form-button',
                                                       'value' => get_string('comparesel', 'socialwiki')));
            echo $OUTPUT->container_end();
        }
        echo html_writer::end_tag('form');
    }

    public function set_allversion($allversion) {
        $this->allversion = $allversion;
    }

    /**
     * Given an array of values, creates a group of radio buttons to be part of a form
     *
     * @param array  $options  An array of value-label pairs for the radio group (values as keys).
     * @param string $name     Name of the radiogroup (unique in the form).
     * @param string $onclick  Function to be executed when the radios are clicked.
     * @param string $checked  The value that is already checked.
     * @param bool   $return   If true, return the HTML as a string, otherwise print it.
     * @return string
     */
    private function choose_from_radio($options, $name, $onclick = '', $checked = '', $return = true) {

        static $idcounter = 0;

        if (!$name) {
            $name = 'unnamed';
        }

        $output = '<span class="radiogroup ' . $name . "\">\n";

        if (!empty($options)) {
            $currentradio = 0;
            foreach ($options as $value => $label) {
                $htmlid = 'auto-rb' . sprintf('%04d', ++$idcounter);
                $output .= ' <span class="radioelement ' . $name . ' rb' . $currentradio . "\">";
                $output .= '<input form = "diff" name="' . $name . '" id="' . $htmlid . '" type="radio" value="' . $value . '"';
                if ($value == $checked) {
                    $output .= ' checked="checked"';
                }
                if ($onclick) {
                    $output .= ' onclick="' . $onclick . '"';
                }
                if ($label === '') {
                    $output .= ' /> <label for="' . $htmlid . '">' . $value . '</label></span>' . "\n";
                } else {
                    $output .= ' /> <label for="' . $htmlid . '">' . $label . '</label></span>' . "\n";
                }
                $currentradio = ($currentradio + 1) % 2;
            }
        }

        $output .= '</span>' . "\n";

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

}

/**
 * The socialwiki home page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_home extends page_socialwiki {

    /**
     * Wiki view option.
     *
     * @var int
     */
    private $view;

    /**
     * Which tab is selected.
     *
     * @var int
     */
    private $tab;

    const EXPLORE_TAB = 0;
    const TOPICS_TAB  = 1;
    const REVIEW_TAB  = 2;
    const PEOPLE_TAB  = 3;

    /**
     * Creates a new home page.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $cm The current course module.
     * @param int $t The tab to view.
     */
    public function __construct($wiki, $subwiki, $cm, $t = 0) {
        Global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        $this->tab = $t;
        $PAGE->set_title(get_string('hometitle', 'socialwiki'));
        $PAGE->requires->js(new moodle_url("table/jquery.dataTables.js"));
        $PAGE->requires->js(new moodle_url("/mod/socialwiki/table/table.js"));
        $PAGE->requires->css(new moodle_url("/mod/socialwiki/table/table.css"));
    }

    /**
     * Sets the URL of the page.
     */
    protected function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/home.php', array('id' => $PAGE->cm->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE, $CFG;
        $PAGE->navbar->add(get_string('home', 'socialwiki'),
                $CFG->wwwroot . '/mod/socialwiki/home.php?id=' . $PAGE->cm->id);
    }

    /**
     * Checks the tab ID and then sets it.
     *
     * @param int $tabid The tab ID to print.
     */
    public function set_tab($tabid) {
        if ($tabid === self::REVIEW_TAB  ||
            $tabid === self::EXPLORE_TAB ||
            $tabid === self::TOPICS_TAB  ||
            $tabid === self::PEOPLE_TAB  ) {
            $this->tab = $tabid;
        }
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $USER, $OUTPUT;

        require_capability(
                'mod/wiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki'
        );

        // Print the home page heading.
        echo $OUTPUT->heading(get_string('hometitle', 'socialwiki'), 1, "socialwiki-title colourtext");

        $userheader = "<div class='home-picture'>";
        $userheader .= $OUTPUT->user_picture(socialwiki_get_user_info($USER->id), array('size' => 65));
        $userheader .= "</div>";
        $userheader .= "<h3 class='home_user'>" . fullname($USER) . "</h3>";
        $userheader .= $this->generate_follow_data();
        echo $userheader;

        echo $this->generate_nav();

        if ($this->tab === self::EXPLORE_TAB) {
            $this->print_explore_tab();
        } else if ($this->tab === self::TOPICS_TAB) {
            $this->print_topics_tab();
        } else if ($this->tab === self::REVIEW_TAB) {
            $this->print_review_tab();
        } else if ($this->tab === self::PEOPLE_TAB) {
            $this->print_people_tab();
        } else {
            echo "ERROR RENDERING PAGE... Invalid tab option";
        }
    }

    public function generate_follow_data() {
        global $USER;
        $followers = socialwiki_get_followers($USER->id, $this->subwiki->id);
        $following = count(socialwiki_get_follows($USER->id, $this->subwiki->id));

        $followdata = html_writer::start_tag('h3', array('class' => 'home_user'));
        $followdata .= html_writer::tag('span', get_string('followers', 'socialwiki') . ": $followers | "
                . get_string('followedusers', 'socialwiki') . ": $following", array('class' => 's-label'));
        $followdata .= html_writer::end_tag('h3');
        return $followdata;
    }

    public function generate_nav() {
        global $PAGE;
        $navlinks = array(
            "Explore" => "home.php?id={$PAGE->cm->id}&tabid=" . self::EXPLORE_TAB,
            "Pages"   => "home.php?id={$PAGE->cm->id}&tabid=" . self::TOPICS_TAB,
            "Manage"  => "home.php?id={$PAGE->cm->id}&tabid=" . self::REVIEW_TAB,
            "People"  => "home.php?id={$PAGE->cm->id}&tabid=" . self::PEOPLE_TAB,
        );

        $count = 0;
        $tabs = array();
        foreach ($navlinks as $label => $link) {
            if ($count++ === $this->tab) {
                $selected = $label;
            }
            $tabs[] = new tabobject($label, $link, $label);
        }

        return $this->wikioutput->tabtree($tabs, $selected, null);
    }

    /**
     * Prints the explore tab.
     */
    public function print_explore_tab() {
        global $USER;
        socialwiki_table::builder($USER->id, $this->subwiki->id, 'pagesfollowed'); // Versions from Following Table.
        socialwiki_table::builder($USER->id, $this->subwiki->id, 'newpages'); // New Versions Table.
        socialwiki_table::builder($USER->id, $this->subwiki->id, 'allpages'); // All Versions Table.
    }

    /**
     * Prints the topics tab.
     */
    public function print_topics_tab() {
        global $USER;
        // Make a new Page button.
        echo "<h2><a style='float:right' class='s-label' href='create.php?action=new"
        . "&swid={$this->subwiki->id}'>" . get_string('makepage', 'socialwiki') . "</a></h2>";
        echo socialwiki_table::builder($USER->id, $this->subwiki->id, 'alltopics'); // All Pages Table.
    }

    /**
     * Prints the review tab.
     */
    public function print_review_tab() {
        Global $USER;
        echo socialwiki_table::builder($USER->id, $this->subwiki->id, 'myfaves'); // Favourites Table.
        echo socialwiki_table::builder($USER->id, $this->subwiki->id, 'mylikes'); // Likes Table.
        echo socialwiki_table::builder($USER->id, $this->subwiki->id, 'mypages'); // My Versions Table.
    }

    /**
     * Prints the people tab.
     */
    public function print_people_tab() {
        global $USER;
        echo socialwiki_table::builder($USER->id, $this->subwiki->id, 'followers'); // Followers Table.
        echo socialwiki_table::builder($USER->id, $this->subwiki->id, 'followedusers'); // Following Table.
        echo socialwiki_table::builder($USER->id, $this->subwiki->id, 'allusers'); // All Users Table.
    }

    public function set_view($option) {
        $this->view = $option;
    }

    protected function render_navigation_node($items, $attrs = array(), $expansionlimit = null, $depth = 1) {
        // Exit if empty, we don't want an empty ul element.
        if (count($items) == 0) {
            return '';
        }

        // Array of nested li elements.
        $lis = array();
        foreach ($items as $item) {
            if (!$item->display) {
                continue;
            }
            $content = $item->get_content();
            $title = $item->get_title();
            if ($item->icon instanceof renderable) {
                $icon = $this->wikioutput->render($item->icon);
                $content = $icon . '&nbsp;' . $content; // Use CSS for spacing of icons.
            }
            if ($item->helpbutton !== null) {
                $content = trim($item->helpbutton) . html_writer::tag('span', $content, array('class' => 'clearhelpbutton'));
            }

            if ($content === '') {
                continue;
            }

            if ($item->action instanceof action_link) {
                // TODO: to be replaced with something else.
                $link = $item->action;
                if ($item->hidden) {
                    $link->add_class('dimmed');
                }
                $content = $this->output->render($link);
            } else if ($item->action instanceof moodle_url) {
                $attributes = array();
                if ($title !== '') {
                    $attributes['title'] = $title;
                }
                if ($item->hidden) {
                    $attributes['class'] = 'dimmed_text';
                }
                $content = html_writer::link($item->action, $content, $attributes);
            } else if (is_string($item->action) || empty($item->action)) {
                $attributes = array();
                if ($title !== '') {
                    $attributes['title'] = $title;
                }
                if ($item->hidden) {
                    $attributes['class'] = 'dimmed_text';
                }
                $content = html_writer::tag('span', $content, $attributes);
            }

            // This applies to the li item which contains all child lists too.
            $liclasses = array($item->get_css_type(), 'depth_' . $depth);
            if ($item->has_children() && (!$item->forceopen || $item->collapse)) {
                $liclasses[] = 'collapsed';
            }
            if ($item->isactive === true) {
                $liclasses[] = 'current_branch';
            }
            $liattr = array('class' => join(' ', $liclasses));
            // Class attribute on the div item which only contains the item content.
            $divclasses = array('tree_item');
            if ((empty($expansionlimit) || $item->type != $expansionlimit) && ($item->children->count() > 0
                                                                        || ($item->nodetype == navigation_node::NODETYPE_BRANCH
                                                                        && $item->children->count() == 0 && isloggedin()))) {
                $divclasses[] = 'branch';
            } else {
                $divclasses[] = 'leaf';
            }
            if (!empty($item->classes) && count($item->classes) > 0) {
                $divclasses[] = join(' ', $item->classes);
            }
            $divattr = array('class' => join(' ', $divclasses));
            if (!empty($item->id)) {
                $divattr['id'] = $item->id;
            }
            $content = html_writer::tag('p', $content, $divattr) .
            $this->render_navigation_node($item->children, array(), $expansionlimit, $depth + 1);
            if (!empty($item->preceedwithhr) && $item->preceedwithhr === true) {
                $content = html_writer::empty_tag('hr') . $content;
            }
            $lis[] = html_writer::tag('li', $content, $liattr);
        }

        if (count($lis)) {
            return html_writer::tag('ul', implode("\n", $lis), $attrs);
        } else {
            return '';
        }
    }

}

/**
 * The socialwiki delete comment page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_deletecomment extends page_socialwiki {

    private $commentid;

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE;
        $PAGE->set_url('/mod/socialwiki/instancecomments.php', array('pageid' => $this->page->id, 'commentid' => $this->commentid));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;
        parent::create_navbar();
        $PAGE->navbar->add(get_string('deletecommentcheck', 'socialwiki'));
    }

    /**
     * Setup page tabs.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'comments', 'activetab' => 'comments'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        $this->printconfirmdelete();
    }

    public function set_action($action, $commentid, $content) {
        $this->action = $action;
        $this->commentid = $commentid;
        $this->content = $content;
    }

    /**
     * Prints the comment deletion confirmation form.
     */
    private function printconfirmdelete() {
        global $OUTPUT;

        $strdeletecheck = get_string('deletecommentcheck', 'socialwiki');
        $strdeletecheckfull = get_string('deletecommentcheckfull', 'socialwiki');

        // Ask confirmation.
        $optionsyes = array('confirm' => 1, 'pageid' => $this->page->id, 'action' => 'delete',
                            'commentid' => $this->commentid, 'sesskey' => sesskey());
        $deleteurl = new moodle_url('/mod/socialwiki/instancecomments.php', $optionsyes);
        $return = new moodle_url('/mod/socialwiki/comments.php', array('pageid' => $this->page->id));

        echo $OUTPUT->heading($strdeletecheckfull);
        print_container_start(false, 'socialwiki-deletecommentform');
        echo '<form class="socialwiki-deletecomment-yes" action="' . $deleteurl . '" method="post" id="deletecomment">';
        echo '<div><input type="submit" name="confirmdeletecomment" value="' . get_string('yes') . '" /></div>';
        echo '</form>';
        echo '<form class="socialwiki-deletecomment-no" action="' . $return . '" method="post">';
        echo '<div><input type="submit" name="norestore" value="' . get_string('no') . '" /></div>';
        echo '</form>';
        print_container_end();
    }

}

/**
 * The sociawiki save page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_save extends page_socialwiki_edit {

    private $newcontent;

    /**
     * Prints out the header at the top of the page.
     */
    public function print_header() {
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $PAGE;

        $context = context_module::instance($PAGE->cm->id);
        require_capability('mod/socialwiki:editpage', $context, null, true, 'noeditpermission', 'socialwiki');

        $this->print_save();
    }

    public function set_newcontent($newcontent) {
        $this->newcontent = $newcontent;
    }

    protected function print_save() {
        global $CFG, $USER;

        $url = $CFG->wwwroot . '/mod/socialwiki/edit.php?pageid=' . $this->page->id . '&makenew=' . $this->makenew;
        if (!empty($this->section)) {
            $url .= "&section=" . urlencode($this->section);
        }

        $params = array(
            'attachmentoptions' => page_socialwiki_edit::$attachmentoptions,
            'format' => $this->format,
            'contextid' => $this->modcontext->id
        );

        if ($this->format != 'html') {
            $params['fileitemid'] = $this->page->id;
            $params['component'] = 'mod_socialwiki';
            $params['filearea'] = 'attachments';
        }

        $form = new mod_socialwiki_edit_form($url, $params);

        $save = false;
        $data = false;
        if ($data = $form->get_data()) {
            if ($this->format == 'html') {
                $data = file_postupdate_standard_editor($data, 'newcontent', page_socialwiki_edit::$attachmentoptions,
                        $this->modcontext, 'mod_socialwiki', 'attachments', $this->subwiki->id);
            }

            if (isset($this->section)) {
                echo "line 2236";
                $save = socialwiki_save_section($this->page, $this->section, $data->newcontent, $USER->id);
                echo "line 2238";
            } else {
                $save = socialwiki_save_page($this->page, $data->newcontent, $USER->id);
            }
        }

        if ($save && $data) {

            $message = '<p>' . get_string('saving', 'socialwiki') . '</p>';

            if (!empty($save['sections'])) {
                foreach ($save['sections'] as $s) {
                    $message .= '<p>' . get_string('repeatedsection', 'socialwiki', $s) . '</p>';
                }
            }

            if (isset($errors) && !empty($errors)) {
                foreach ($errors as $e) {
                    $message .= "<p>" . get_string('filenotuploadederror', 'socialwiki', $e->get_filename()) . "</p>";
                }
            }

            $url = new moodle_url('/mod/socialwiki/view.php',
                                  array('pageid' => $this->page->id, 'group' => $this->subwiki->groupid));
            redirect($url);
        } else {
            print_error('savingerror', 'socialwiki');
        }
    }

}

/**
 * The socialwiki pretty view page (for printing) class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_prettyview extends page_socialwiki {

    /**
     * Prints out the header at the top of the page.
     */
    public function print_header() {
        global $PAGE, $OUTPUT;
        $PAGE->set_pagelayout('embedded');
        echo $OUTPUT->header();

        echo '<h1 id="socialwiki-printable-title">' . format_string($this->title) . '</h1>';
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;

        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/prettyview.php', array('pageid' => $this->page->id));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        require_capability('mod/socialwiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');

        $this->print_pretty_view();
    }

    /**
     * Prints out the pretty view content.
     */
    private function print_pretty_view() {
        $content = socialwiki_parse_content($this->page->format, $this->page->content,
                                            array('printable' => true, 'swid' => $this->subwiki->id,
                                                  'pageid' => $this->page->id, 'pretty_print' => true));

        echo '<div id="socialwiki-printable-content">';
        echo format_text($content['parsed_text'], FORMAT_HTML);
        echo '</div>';
    }

}

/**
 * The socialwiki page for handling comments class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_handlecomments extends page_socialwiki {

    private $action;
    private $content;
    private $commentid;
    private $format;


    /**
     * Prints out the header at the top of the page.
     */
    public function print_header() {
        $this->set_url();
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/comments.php', array('pageid' => $this->page->id));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $CFG, $USER;

        if ($this->action == 'add') {
            if (has_capability('mod/socialwiki:editcomment', $this->modcontext)) {
                $this->add_comment($this->content, $this->commentid);
            }
        } else if ($this->action == 'edit') {
            $comment = socialwiki_get_comment($this->commentid);
            $edit = has_capability('mod/socialwiki:editcomment', $this->modcontext);
            $owner = ($comment->userid == $USER->id);
            if ($owner && $edit) {
                $this->add_comment($this->content, $this->commentid);
            }
        } else if ($this->action == 'delete') {
            $comment = socialwiki_get_comment($this->commentid);
            $manage = has_capability('mod/socialwiki:managecomment', $this->modcontext);
            $owner = ($comment->userid == $USER->id);
            if ($owner || $manage) {
                $this->delete_comment($this->commentid);
                redirect($CFG->wwwroot . '/mod/socialwiki/comments.php?pageid=' .
                         $this->page->id, get_string('deletecomment', 'socialwiki'), 2);
            }
        }
    }

    public function set_action($action, $commentid, $content) {
        $this->action = $action;
        $this->commentid = $commentid;
        $this->content = $content;
        $this->format = $this->page->format;
    }

    private function add_comment($content, $idcomment) {
        global $CFG;
        require_once($CFG->dirroot . "/mod/socialwiki/locallib.php");

        $pageid = $this->page->id;

        socialwiki_add_comment($this->modcontext, $pageid, $content, $this->format);

        if (!$idcomment) {
            redirect($CFG->wwwroot . '/mod/socialwiki/comments.php?pageid=' .
                     $pageid, get_string('createcomment', 'socialwiki'), 2);
        } else {
            $this->delete_comment($idcomment);
            redirect($CFG->wwwroot . '/mod/socialwiki/comments.php?pageid=' .
                     $pageid, get_string('editingcomment', 'socialwiki'), 2);
        }
    }

    private function delete_comment($commentid) {
        $pageid = $this->page->id;
        socialwiki_delete_comment($commentid, $this->modcontext, $pageid);
    }

}

/**
 * The socialwiki administration page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_admin extends page_socialwiki {

    public $view, $action;
    public $listall = false;

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/admin.php', array('pageid' => $this->page->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;
        parent::create_navbar();
        $PAGE->navbar->add(get_string('admin', 'socialwiki'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $OUTPUT;
        // Make sure anyone trying to access this page has managewiki capabilities.
        require_capability('mod/socialwiki:managewiki', $this->modcontext,
                           null, true, 'noviewpagepermission', 'socialwiki');

        // Display admin menu.
        $link = socialwiki_parser_link($this->page);
        $class = ($link['new']) ? 'class="socialwiki-newentry"' : '';
        $pagelink = '<a href="' . $link['url'] . '"' . $class . '>' .
            format_string($link['content']) . ' (ID:' . $this->page->id . ')' . '</a>';
        $urledit = new moodle_url('/mod/socialwiki/edit.php', array('pageid' => $this->page->id, 'sesskey' => sesskey()));
        $urldelete = new moodle_url('/mod/socialwiki/admin.php', array(
                'pageid'  => $this->page->id,
                'delete'  => $this->page->id,
                'option'  => $this->view,
                'listall' => !$this->listall ? '1' : '',
                'sesskey' => sesskey()));

        $editlinks = $OUTPUT->action_icon($urledit, new pix_icon('t/edit', get_string('edit')));
        $editlinks .= $OUTPUT->action_icon($urldelete, new pix_icon('t/delete', get_string('delete')));
        echo "Current Page: $pagelink $editlinks";
        $this->print_delete_content($this->listall);
    }

    /**
     * Sets admin view option
     *
     * @param int $view Page view ID.
     * @param bool $listall Is only valid for view 1.
     */
    public function set_view($view, $listall = true) {
        $this->view = $view;
        $this->listall = $listall;
    }

    /**
     * Show wiki page delete options
     *
     * @param bool $showall
     */
    protected function print_delete_content($showall = false) {
        $table = new html_table();
        $table->head = array(get_string('pagename', 'socialwiki'), '');
        $table->attributes['style'] = 'width:auto';
        $swid = $this->subwiki->id;
        if (!$showall) {
            if ($relatedpages = socialwiki_get_related_pages($swid, $this->title)) {
                $this->add_page_delete_options($relatedpages, $table);
            } else {
                $table->data[] = array('', get_string('noorphanedpages', 'socialwiki'));
            }
        } else {
            if ($pages = socialwiki_get_page_list($swid)) {
                $this->add_page_delete_options($pages, $table);
            } else {
                $table->data[] = array('', get_string('nopages', 'socialwiki'));
            }
        }

        // Print the form.
        echo html_writer::start_tag('form', array(
            'action' => new moodle_url('/mod/socialwiki/admin.php'),
            'method' => 'post'));
        echo html_writer::tag('div', html_writer::empty_tag('input', array(
                'type'  => 'hidden',
                'name'  => 'pageid',
                'value' => $this->page->id)));

        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'option', 'value' => $this->view));
        echo html_writer::table($table);
        if ($showall) {
            echo html_writer::empty_tag('input', array(
                'type'    => 'submit',
                'class'   => 'socialwiki-form-button',
                'value'   => get_string('listrelated', 'socialwiki'),
                'sesskey' => sesskey()));
        } else {
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'listall', 'value' => '1'));
            echo html_writer::empty_tag('input', array(
                'type'    => 'submit',
                'class'   => 'socialwiki-form-button',
                'value'   => get_string('listall', 'socialwiki'),
                'sesskey' => sesskey()));
        }
        echo html_writer::end_tag('form');
    }

    /**
     * Helper function for print_delete_content. This will add data to the table.
     *
     * @param array $pages Set of pages to show.
     * @param stdClass $table Reference to the table in which data needs to be added.
     */
    protected function add_page_delete_options($pages, &$table) {
        global $OUTPUT;
        foreach ($pages as $page) {
            $link = socialwiki_parser_link($page);
            $class = ($link['new']) ? 'class="socialwiki-newentry"' : '';
            $pagelink = '<a href="' . $link['url'] . '"' . $class . '>' .
                format_string($link['content']) . ' (ID:' . $page->id . ')' . '</a>';
            $urledit = new moodle_url('/mod/socialwiki/edit.php', array('pageid' => $page->id, 'sesskey' => sesskey()));
            $urldelete = new moodle_url('/mod/socialwiki/admin.php', array(
                'pageid'  => $this->page->id,
                'delete'  => $page->id,
                'option'  => $this->view,
                'listall' => !$this->listall ? '1' : '',
                'sesskey' => sesskey()));

            $editlinks = $OUTPUT->action_icon($urledit, new pix_icon('t/edit', get_string('edit')));
            $editlinks .= $OUTPUT->action_icon($urldelete, new pix_icon('t/delete', get_string('delete')));
            $table->data[] = array($pagelink, $editlinks);
        }
    }
}

/**
 * The socialwiki user profile page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_viewuserpages extends page_socialwiki {

    /**
     * Creates a new profile page.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $cm The current course module.
     * @param int $targetuser The target user ID.
     */
    public function __construct($wiki, $subwiki, $cm, $targetuser) {
        Global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        $this->uid = $targetuser;
        $PAGE->set_title(fullname(socialwiki_get_user_info($targetuser)));
        $PAGE->requires->js(new moodle_url("table/jquery.dataTables.js"));
        $PAGE->requires->js(new moodle_url("/mod/socialwiki/table/table.js"));
        $PAGE->requires->css(new moodle_url("/mod/socialwiki/table/table.css"));
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;

        $params = array('userid' => $this->uid, 'subwikiid' => $this->subwiki->id);
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/viewuserpages.php', $params);
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE, $CFG;
        $PAGE->navbar->add(get_string('viewuserpages', 'socialwiki'), $CFG->wwwroot .
            '/mod/socialwiki/viewuserpages.php?userid=' . $this->uid . '&subwikiid=' . $this->subwiki->id);
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        Global $OUTPUT, $CFG, $USER, $PAGE;
        require_once($CFG->dirroot . '/mod/socialwiki/peer.php');

        $user = socialwiki_get_user_info($this->uid);
        $scale = array('like' => 1, 'trust' => 1, 'follow' => 1, 'popular' => 1);
        $context = context_module::instance($PAGE->cm->id);
        $numpeers = count(get_enrolled_users($context)) - 1;
        // Get this user's peer score.
        $peer = socialwiki_peer::socialwiki_get_peer($user->id, $this->subwiki->id, $USER->id, $numpeers, $scale);

        // USER INFO OUTPUT.
        $html = $OUTPUT->heading(fullname($user), 1, 'colourtext');
        $html .= "<div class='home-picture'>";
        $html .= $OUTPUT->user_picture($user, array('size' => 100));
        $html .= "</div>";

        // Result placed in table below.
        // Don't show peer scores if user is viewing themselves.
        if ($USER->id != $user->id) {
            // PEER SCORES OUTPUT.
            $html .= $OUTPUT->container_start('peer-info colourtext');
            $table = new html_table();
            $table->head = array(get_string('peerscores', 'socialwiki'));
            $table->attributes['class'] = 'peer-table colourtext';
            $table->align = array('left');
            $table->data = array();

            // Make button to follow/unfollow.
            if (!socialwiki_is_following($USER->id, $user->id, $this->subwiki->id) && $USER->id != $this->uid) {
                $icon = new moodle_url('/mod/socialwiki/pix/icons/follow.png');
                $text = get_string('follow', 'socialwiki');
                $tip = get_string('follow_tip', 'socialwiki');
            } else if ($USER->id != $this->uid) {
                // Show like link.
                $icon = new moodle_url('/mod/socialwiki/pix/icons/unfollow.png');
                $text = get_string('unfollow', 'socialwiki');
                $tip = get_string('unfollow_tip', 'socialwiki');
            }
            $followaction = $CFG->wwwroot . '/mod/socialwiki/follow.php';

            $followbtn = html_writer::start_tag('form', array('style' => "display: inline",
                                              'action' => $followaction, "method" => "get"));
            $followbtn .= '<input type ="hidden" name="user2" value="' . $user->id . '"/>';
            $followbtn .= '<input type ="hidden" name="from" value="' . $CFG->wwwroot .
                '/mod/socialwiki/viewuserpages.php?userid=' . $user->id . '&subwikiid=' . $this->subwiki->id . '"/>';
            $followbtn .= '<input type ="hidden" name="swid" value="' . $this->subwiki->id . '"/>';
            $followbtn .= '<input type ="hidden" name="sesskey" value="' . sesskey() . '"/>';
            $followbtn .= html_writer::start_tag('button', array('class' => 'socialwiki_followbutton',
                'id' => 'followlink', 'title' => $tip));
            $followbtn .= html_writer::tag('img', '', array('src' => $icon));
            $followbtn .= $text;
            $followbtn .= html_writer::end_tag('button');
            $followbtn .= html_writer::end_tag('form');

            $row1 = new html_table_row(array(get_string('networkdistance', 'socialwiki').':', $peer->depth, $followbtn));
            $row1->cells[2]->rowspan = 3;

            $table->data[] = $row1;
            $table->data[] = array(get_string('followsim', 'socialwiki').':', $peer->followsim);
            $table->data[] = array(get_string('likesim', 'socialwiki').':', $peer->likesim);
            $table->data[] = array(get_string('popularity', 'socialwiki').':', $peer->popularity);

            $html .= html_writer::table($table);
            $html .= $OUTPUT->container_end();
        }
        echo $html;

        // Favourites Table.
        socialwiki_table::builder($this->uid, $this->subwiki->id, 'userfaves');
        // User Verions Table.
        socialwiki_table::builder($this->uid, $this->subwiki->id, 'userpages');
    }
}
