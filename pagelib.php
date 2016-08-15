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
     * Current wiki.
     *
     * @var stdClass
     */
    protected $wiki;

    /**
     * Current subwiki.
     *
     * @var stdClass
     */
    protected $subwiki;

    /**
     * Current page.
     *
     * @var stdClass
     */
    protected $page;

    /**
     * Current page title.
     *
     * @var string
     */
    public $title;

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
    protected $tabs = array('view', 'edit', 'versions', 'admin');
    // All tabs: home, view, edit, comments, versions, admin.

    /**
     * The tab options.
     *
     * @var array
     */
    protected $taboptions = array();

    /**
     * Wiki renderer.
     *
     * @var mod_socialwiki_renderer
     */
    protected $wikioutput;

    /**
     * The CSS style.
     *
     * @var stdClass
     */
    protected $style;

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

        $this->print_help();
        $this->print_pagetitle();
        $this->setup_tabs();
        // Tabs are associated with pageid, so if page is empty, tabs should be disabled.
        if (!empty($this->page) && !empty($this->tabs)) {
            $tabthing = $this->wikioutput->tabs($this->page, $this->tabs, $this->taboptions); // Calls tabs function in renderer.
            echo $tabthing;
        }
    }

    /**
     * Prints the help button.
     */
    public function print_help() {
        global $PAGE;
        $html = html_writer::start_tag('form', array('action' => 'help.php#'
            . basename(filter_input(INPUT_SERVER, 'PHP_SELF'), '.php'), 'target' => '_blank'));
        $html .= '<input type="hidden" name="id" value="' . $PAGE->cm->id . '"/>';
        $html .= '<input value="' . get_string('help', 'socialwiki') . '" type="submit" class="helpbtn">';
        $html .= html_writer::end_tag('form');
        echo $html;
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
        echo $OUTPUT->heading(format_string($this->title), 2);
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

        if ($groupmode && $groupmode == VISIBLEGROUPS) {
            $currentgroup = groups_get_activity_group($PAGE->cm);
            $manage = has_capability('mod/socialwiki:managewiki', $PAGE->cm->context);
            $edit = has_capability('mod/socialwiki:editpage', $PAGE->context);
            if (!$manage && !($edit && groups_is_member($currentgroup))) {
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
     * Prints out the header at the top of the page.
     */
    public function print_header() {
        global $PAGE;
        // Print styling.
        $PAGE->requires->css(new moodle_url("/mod/socialwiki/print.css"));
        // JS code for the ajax-powered like button.
        $PAGE->requires->js(new moodle_url("/mod/socialwiki/view.js"));
        parent::print_header();
        $this->wikioutput->socialwiki_print_subwiki_selector($PAGE->activityrecord, $this->subwiki, $this->page, 'view');
    }

    /**
     * Do not show the help button here.
     */
    public function print_help() {
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $params = array();

        if (!empty($this->page) && $this->page != null) {
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
     * Prints the page title, including the like button.
     */
    protected function print_pagetitle() {
        global $CFG;
        $key = sesskey();
        echo '<script> var options="?pageid='.$this->page->id.'&sesskey='.$key.'"</script>'; // Passed to ajax liker.

        $isliked = socialwiki_liked($this->uid, $this->page->id);
        $likecurrent = ($isliked ? 'unlike' : 'like');
        $likeother = (!$isliked ? 'unlike' : 'like');
        $pixurl = new moodle_url('/mod/socialwiki/pix/icons/');

        $link = '';
        if (has_capability('mod/socialwiki:editpage', $this->modcontext)) {
            $link = "$CFG->wwwroot/mod/socialwiki/like.php?pageid={$this->page->id}&sesskey=$key";
        }
        $numlikes = socialwiki_numlikes($this->page->id);
        $liker = html_writer::start_tag('a', array('id' => 'socialwiki-like',
            'class' => $isliked ? 'liked' : '', 'href' => $link, 'title' => get_string('like_tip', 'socialwiki')));
        $liker .= html_writer::tag('img', "", array('src' => $pixurl . $likecurrent . '.png',
            'other' => $pixurl . $likeother . '.png'));
        $liker .= html_writer::tag('span', "$numlikes " . get_string($numlikes === 1 ? 'like' : 'likes', 'socialwiki'));
        $liker .= html_writer::end_tag('a');
        echo $liker;

        parent::print_pagetitle();

        $params = array('pageid' => $this->page->id);
        $this->wikioutput->navigator($params, $this->page->id, $this->subwiki->id);
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        if (socialwiki_user_can_view($this->subwiki)) {
            if (!empty($this->page)) {
                echo socialwiki_print_page_content($this->page, $this->modcontext, $this->subwiki->id);
            } else {
                echo get_string('nocontent', 'socialwiki');
            }
        } else {
            echo get_string('cannotviewpage', 'socialwiki');
        }
    }

    /**
     * Print comments.
     */
    public function print_comments() {
        global $CFG, $OUTPUT, $USER;
        $course = get_context_info_array($this->modcontext->id)[1];

        if (!has_capability('mod/socialwiki:viewcomment', $this->modcontext)) {
            return;
        }

        echo '<hr>';
        $comments = socialwiki_get_comments($this->modcontext->id, $this->page->id);

        // Add comment button.
        if (has_capability('mod/socialwiki:editcomment', $this->modcontext)) {
            echo "<div class='midpad'><a href='$CFG->wwwroot/mod/socialwiki/editcomments.php?action=add&amp;pageid="
                . "{$this->page->id}'>" . get_string('addcomment', 'socialwiki') . "</a></div>";
        }

        // Show reversible button.
        if (count($comments) > 1) {
            echo '<a id="socialwiki-comdirection" other="'
                . get_string('commentoldest', 'socialwiki') . '")>' . get_string('commentnewest', 'socialwiki') . '</a>';
        }

        // List comments.
        echo '<ol class="socialwiki-commentlist reversed">';
        foreach ($comments as $comment) {
            $user = socialwiki_get_user_info($comment->userid);

            // Get the user name and date of posting.
            $info = '<b><a href="' . $CFG->wwwroot . '/mod/socialwiki/viewuserpages.php?userid='
                    . $user->id . '&amp;subwikiid=' . $this->page->subwikiid . '">'
                    . fullname($user, has_capability('moodle/site:viewfullnames', context_course::instance($course->id)))
                    . '</a></b> ' . socialwiki_format_time($comment->timecreated) . ' ';

            // Check if edit and delete icons should be shown.
            if (has_capability('mod/socialwiki:managecomment', $this->modcontext) ||
                (has_capability('mod/socialwiki:editcomment', $this->modcontext) && $USER->id == $user->id)) {
                $urledit = new moodle_url('/mod/socialwiki/editcomments.php',
                        array('commentid' => $comment->id, 'pageid' => $this->page->id, 'action' => 'edit'));
                $urldelet = new moodle_url('/mod/socialwiki/instancecomments.php',
                        array('commentid' => $comment->id, 'pageid' => $this->page->id, 'action' => 'delete'));
                $info .= $OUTPUT->action_icon($urledit, new pix_icon('t/edit', get_string('edit'), "",
                        array('class' => 'iconsmall'))) . $OUTPUT->action_icon($urldelet,
                        new pix_icon('t/delete', get_string('delete'), "", array('class' => 'iconsmall')));
            }

            // Print out the full comment.
            echo "<li><div style='float:left; margin-right:12px'>" . $OUTPUT->user_picture($user, array('popup' => true))
                . "</div>$info<div>$comment->content</div></li>";
        }
        echo '</ol>';
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

    /**
     * Prints the editing content pane.
     *
     * @param string $content The current content from the previous version.
     * @param bool $notify Whether to show a notification.
     */
    protected function print_edit($content = null, $notify = true) {
        global $CFG, $OUTPUT;

        if ($notify) {
            echo $OUTPUT->notification(get_string('editwarning', 'socialwiki'), 'info');
        }

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
        $data->format = $this->page->format;

        if ($this->page->format === 'html') {
            $data->newcontentformat = FORMAT_HTML;
            // Append editor context to editor options, giving preference to existing context.
            self::$attachmentoptions = array_merge(
                    array('context' => $this->modcontext), self::$attachmentoptions);
            $data = file_prepare_standard_editor($data, 'newcontent', self::$attachmentoptions,
                    $this->modcontext, 'mod_socialwiki', 'attachments', $this->subwiki->id);
        } else {
            $params['fileitemid'] = $this->subwiki->id;
            $params['component'] = 'mod_socialwiki';
            $params['filearea'] = 'attachments';
            echo '<a href="' . $CFG->wwwroot . '/mod/socialwiki/files.php?swid='
                    . $this->subwiki->id . '">' . get_string('uploadtitle', 'socialwiki') . '</a>';
        }
        $form = new mod_socialwiki_edit_form($url, $params);
        $form->set_data($data);
        $form->display();
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

    /**
     * @var moodleform
     */
    private $form;

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/editcomments.php', array('pageid' => $this->page->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE, $CFG;

        $PAGE->navbar->add(get_string('comments', 'socialwiki'), $CFG->wwwroot
                . '/mod/socialwiki/view.php?pageid=' . $this->page->id);

        if ($this->action == 'add') {
            $PAGE->navbar->add(get_string('insertcomment', 'socialwiki'));
        } else {
            $PAGE->navbar->add(get_string('editcomment', 'socialwiki'));
        }
    }

    /**
     * Setup page tabs.
     *
     * @param array $options Not used in this case.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'comments', 'activetab' => 'comments'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        require_capability('mod/socialwiki:editcomment', $this->modcontext, null, true, 'noeditcommentpermission', 'socialwiki');

        // Setup a new comment or put together a comment to edit.
        $com = new stdClass();
        if ($this->action === 'edit') {
            $com = $this->comment;
            $com->entrycomment_editor['text'] = $com->content;
        }
        $com->action = $this->action;
        $com->commentoptions = array('trusttext' => true, 'maxfiles' => 0);

        $this->form->set_data($com);
        $this->form->display();
    }

    public function set_action($action, $comment) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/socialwiki/comments_form.php');

        $this->action = $action;
        $this->comment = $comment;

        $destination = $CFG->wwwroot . '/mod/socialwiki/instancecomments.php?pageid=' . $this->page->id;
        $this->form = new mod_socialwiki_comments_form($destination);
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
    private $groups;

    /**
     * @var moodleform
     */
    private $form;

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
        $PAGE->set_url(new moodle_url('/mod/socialwiki/create.php', $params));
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
        $this->form = new mod_socialwiki_create_form($url->out(false), $options);
    }

    /**
     * Prints the page content.
     *
     * @param string $pagetitle The page title.
     */
    public function print_content($pagetitle = "") {
        global $PAGE;

        // TODO: Change this to has_capability and show an alternative interface.
        require_capability('mod/socialwiki:createpage', $this->modcontext, null, true, 'nocreatepermission', 'socialwiki');
        $data = new stdClass();
        if (!empty($pagetitle)) {
            $data->pagetitle = $pagetitle;
        }
        $data->pageformat = $PAGE->activityrecord->defaultformat;

        $this->form->set_data($data);
        $this->form->display();
    }

    public function create_page($pagetitle) {
        global $USER, $PAGE;

        $data = $this->form->get_data();
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
     *
     * @param array $options Not used in this case.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'edit', 'activetab' => 'edit'));
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

        $options = array('swid' => $this->page->subwikiid, 'pageid' => $this->page->id, 'pretty_print' => true, 'navi' => 0);

        if ($data = $form->get_data()) {
            if (isset($data->newcontent)) {
                // Wiki format.
                $text = $data->newcontent;
            } else {
                // HTML format.
                $text = $data->newcontent_editor['text'];
            }
            $parserout = socialwiki_parse_content($data->contentformat, $text, $options);
            $this->set_newcontent($text);
            echo $OUTPUT->notification(get_string('previewwarning', 'socialwiki'), 'notifyproblem');
            $parsedcontent = format_text($parserout['parsed_text'], FORMAT_HTML, array('overflowdiv' => true, 'filter' => false));
            echo $OUTPUT->box($parsedcontent, 'socialwiki-previewbox');
            $content = $this->newcontent;
        }

        $this->print_edit($content, false);
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
        $PAGE->navbar->add(get_string('versions', 'socialwiki'), $CFG->wwwroot
                . '/mod/socialwiki/versions.php?pageid=' . $this->page->id);
        $PAGE->navbar->add(get_string('diff', 'socialwiki'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        require_capability('mod/socialwiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');

        $oldversion = socialwiki_get_page($this->compare);
        $newversion = socialwiki_get_page($this->comparewith);

        if ($oldversion && $newversion) {
            $oldtext = format_text(file_rewrite_pluginfile_urls($oldversion->content,
                    'pluginfile.php', $this->modcontext->id, 'mod_socialwiki', 'attachments', $this->subwiki->id));
            $newtext = format_text(file_rewrite_pluginfile_urls($newversion->content,
                    'pluginfile.php', $this->modcontext->id, 'mod_socialwiki', 'attachments', $this->subwiki->id));
            list($diff1, $diff2) = socialwiki_diff_html($oldtext, $newtext);
            $oldversion->diff = $diff1;
            $oldversion->user = socialwiki_get_user_info($oldversion->userid);
            $newversion->diff = $diff2;
            $newversion->user = socialwiki_get_user_info($newversion->userid);

            echo $this->wikioutput->diff($this->page->id, $oldversion, $newversion);
        } else {
            print_error('versionerror', 'socialwiki');
        }
    }

    public function set_comparison($compare, $comparewith) {
        $this->compare = $compare;
        $this->comparewith = $comparewith;
    }
}

/**
 * The socialwiki versions page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_versions extends page_socialwiki {

    /**
     * The view mode for viewing results.
     *
     * @var int
     */
    public $view;

    /**
     * Creates a new versions page.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $cm The current course module.
     * @param int $view Specifying either tree or table view.
     */
    public function __construct($wiki, $subwiki, $cm, $view) {
        global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        $this->view = $view;
        if ($this->view == 1) {
            // For table view.
            $PAGE->requires->js(new moodle_url("table/datatables.min.js"));
            $PAGE->requires->js(new moodle_url("/mod/socialwiki/table/table.js"));
            $PAGE->requires->css(new moodle_url("/mod/socialwiki/table/table.css"));
        } else {
            // For tree view.
            $PAGE->requires->js(new moodle_url("/mod/socialwiki/tree/tree.js"));
            $PAGE->requires->css(new moodle_url("/mod/socialwiki/tree/tree.css"));
        }
    }

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/versions.php', array('pageid' => $this->page->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    protected function create_navbar() {
        global $PAGE;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('versions', 'socialwiki'));
    }

    /**
     * Setup page tabs.
     *
     * @param array $options Not used in this case.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'versions', 'activetab' => 'versions'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        require_capability('mod/socialwiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');
        $params = array('pageid' => $this->page->id);
        $this->wikioutput->versions('versions', $params,
            socialwiki_get_relations($this->page->id), $this->view, $this->subwiki->id, $this->page->id);
    }
}

/**
 * The socialwiki search page class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_search extends page_socialwiki_versions {

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
     * 1 for an exact search type.
     *
     * @var int
     */
    private $exact;

    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        if (isset($this->page)) {
            $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/search.php?pageid='
                    . $this->page->id . '&id=' . $PAGE->cm->id);
        } else {
            $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/search.php?id=' . $PAGE->cm->id);
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
     * @param bool $searchtitle Whether to search for the page title.
     * @param bool $searchcontent Whether to search the page content.
     * @param bool $exactmatch An exact match will only return pages with the exact title.
     */
    public function set_search_string($search, $searchtitle, $searchcontent, $exactmatch = false) {
        $this->searchstring = $search;
        $this->exact = $exactmatch;
        $this->searchresult = socialwiki_search($this->subwiki->id, $search, $searchtitle, $searchcontent, $exactmatch);
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $PAGE;
        require_capability('mod/socialwiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');
        $params = array('searchstring' => $this->searchstring, 'id' => $PAGE->cm->id, 'exact' => $this->exact);
        $this->wikioutput->versions('search', $params, $this->searchresult, $this->view, $this->subwiki->id);
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
     * Which tab is selected.
     *
     * @var int
     */
    private $tab = 0;

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
     */
    public function __construct($wiki, $subwiki, $cm) {
        Global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);

        $PAGE->requires->js(new moodle_url("table/datatables.min.js"));
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
        $PAGE->navbar->add(get_string('home', 'socialwiki'), $CFG->wwwroot . '/mod/socialwiki/home.php?id=' . $PAGE->cm->id);
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

        require_capability('mod/wiki:viewpage', $this->modcontext, null, true, 'noviewpagepermission', 'socialwiki');

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
        $selected = "";
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
        $newpagebtn = "";
        if (has_capability('mod/socialwiki:editpage', $this->modcontext)) {
            $newpagebtn .= html_writer::start_tag('form', array('style' => "float:right; margin: 0;", 'action' => 'create.php'));
            $newpagebtn .= '<input type="hidden" name="swid" value="' . $this->subwiki->id . '"/>';
            $newpagebtn .= '<input value="' . get_string('makepage', 'socialwiki')
                . '" type="submit" id="id_submitbutton" style="margin: 0; position: relative; z-index: 1;">';
            $newpagebtn .= html_writer::end_tag('form');
        }
        echo $newpagebtn , socialwiki_table::builder($USER->id, $this->subwiki->id, 'alltopics'); // All Pages Table.
    }

    /**
     * Prints the review tab.
     */
    public function print_review_tab() {
        global $USER;
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
     *
     * @param array $options Not used in this case.
     */
    protected function setup_tabs($options = array()) {
        parent::setup_tabs(array('linkedwhenactive' => 'comments', 'activetab' => 'comments'));
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $CFG,  $OUTPUT;
        $course = get_context_info_array($this->modcontext->id)[1];

        $strdeletecheckfull = get_string('deletecommentcheckfull', 'socialwiki');

        // Ask confirmation.
        $optionsyes = array('confirm' => 1, 'pageid' => $this->page->id, 'action' => 'delete',
            'commentid' => $this->commentid, 'sesskey' => sesskey());
        $deleteurl = new moodle_url('/mod/socialwiki/instancecomments.php', $optionsyes);
        $return = new moodle_url('/mod/socialwiki/view.php', array('pageid' => $this->page->id));

        // Print the comment deletion confirmation form.
        echo $OUTPUT->heading($strdeletecheckfull, 3);
        echo '<form class="socialwiki-deletecomment" action="' . $deleteurl . '" method="post" id="deletecomment">' .
            '<input type="submit" name="confirmdeletecomment" value="' . get_string('yes') . '" /></form>' .
            '<form class="socialwiki-deletecomment" action="' . $return . '" method="post">' .
            '<input type="submit" name="norestore" value="' . get_string('no') . '" /></form>';

        $comment = socialwiki_get_comment($this->commentid);
        $user = socialwiki_get_user_info($comment->userid);

        // Get the user name and date of posting.
        $info = '<b><a href="' . $CFG->wwwroot . '/mod/socialwiki/viewuserpages.php?userid='
            . $user->id . '&amp;subwikiid=' . $this->page->subwikiid . '">'
            . fullname($user, has_capability('moodle/site:viewfullnames', context_course::instance($course->id)))
            . '</a></b> ' . socialwiki_format_time($comment->timecreated) . ' ';

        // Print out the full comment.
        echo "<div style='margin:1em 2em'><div style='float:left; margin-right:12px'>"
            . $OUTPUT->user_picture($user, array('popup' => true)) . "</div>$info<div>$comment->content</div></div>";
    }

    public function set_action($action, $commentid) {
        // Action is unused (parity with handlecomments).
        $this->commentid = $commentid;
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
        global $PAGE, $CFG, $USER;

        $context = context_module::instance($PAGE->cm->id);
        require_capability('mod/socialwiki:editpage', $context, null, true, 'noeditpermission', 'socialwiki');

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

            redirect(new moodle_url('/mod/socialwiki/view.php',
                    array('pageid' => $this->page->id, 'group' => $this->subwiki->groupid)));
        } else {
            print_error('savingerror', 'socialwiki');
        }
    }

    public function set_newcontent($newcontent) {
        $this->newcontent = $newcontent;
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
        $this->set_url();
        $PAGE->set_pagelayout('embedded');
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($this->title), 1, 'socialwiki-printable-title');
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

        $content = socialwiki_parse_content($this->page->format, $this->page->content,
                array('printable' => true, 'swid' => $this->subwiki->id, 'pageid' => $this->page->id, 'pretty_print' => true));

        echo '<div id="socialwiki-printable-content">' , format_text($content['parsed_text'], FORMAT_HTML) , '</div>';
    }
}

/**
 * The socialwiki page for handling comments class.
 *
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_socialwiki_handlecomment extends page_socialwiki {

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
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/instancecomments.php', array('pageid' => $this->page->id));
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
                redirect($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' .
                         $this->page->id, get_string('deletecomment', 'socialwiki'), 2);
            }
        }
    }

    public function set_action($action, $commentid, $content = 0) {
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
            redirect($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' .
                     $pageid, get_string('createcomment', 'socialwiki'), 2);
        } else {
            $this->delete_comment($idcomment);
            redirect($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' .
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
        $class = ($link['new']) ? 'class="socialwiki-newentry"' : "";
        $pagelink = '<a href="' . $link['url'] . '"' . $class . '>' .
            format_string($link['content']) . ' (ID:' . $this->page->id . ')' . '</a>';
        $urledit = new moodle_url('/mod/socialwiki/edit.php', array('pageid' => $this->page->id, 'sesskey' => sesskey()));
        $urldelete = new moodle_url('/mod/socialwiki/admin.php', array(
                'pageid'  => $this->page->id,
                'delete'  => $this->page->id,
                'sesskey' => sesskey()));

        $editlinks = $OUTPUT->action_icon($urledit, new pix_icon('t/edit', get_string('edit')));
        $editlinks .= $OUTPUT->action_icon($urldelete, new pix_icon('t/delete', get_string('delete')));
        echo get_string('viewcurrent', 'socialwiki') . ": $pagelink $editlinks";
        $this->print_delete_content($this->listall);
    }

    /**
     * Sets admin view option
     *
     * @param bool $listall Is only valid for view 1.
     */
    public function set_view($listall = false) {
        $this->listall = $listall;
    }

    /**
     * Show wiki page delete options
     *
     * @param bool $showall
     */
    protected function print_delete_content($showall = false) {
        // Print the form.
        echo html_writer::start_tag('form', array(
            'action' => new moodle_url('/mod/socialwiki/admin.php'),
            'method' => 'post'));
        echo html_writer::tag('div', html_writer::empty_tag('input', array(
                'type'  => 'hidden',
                'name'  => 'pageid',
                'value' => $this->page->id)));

        // Build the pages table.
        $table = new html_table();
        $table->head = array(get_string('pagename', 'socialwiki'), "");
        $table->attributes['style'] = 'width:auto';
        $swid = $this->subwiki->id;
        if (!$showall) {
            if ($relatedpages = socialwiki_get_related_pages($swid, $this->title)) {
                $this->add_page_delete_options($relatedpages, $table);
            } else {
                $table->data[] = array("", get_string('noorphanedpages', 'socialwiki'));
            }
        } else {
            if ($pages = socialwiki_get_page_list($swid, false)) {
                $this->add_page_delete_options($pages, $table);
            } else {
                $table->data[] = array("", get_string('nopages', 'socialwiki'));
            }
        }
        echo html_writer::table($table);

        if ($showall) {
            // Print a button to show related pages.
            echo html_writer::empty_tag('input', array(
                'type'    => 'submit',
                'value'   => get_string('listrelated', 'socialwiki'),
                'sesskey' => sesskey()));
        } else {
            // Tag to list all pages.
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'listall', 'value' => '1'));
            // Print a button to show all pages.
            echo html_writer::empty_tag('input', array(
                'type'    => 'submit',
                'value'   => get_string('listall', 'socialwiki'),
                'sesskey' => sesskey()));
        }
        echo html_writer::end_tag('form');
    }

    /**
     * Helper function for print_delete_content. This will add data to the table.
     *
     * @param array $pages Set of pages to show.
     * @param html_table $table Reference to the table in which data needs to be added.
     */
    protected function add_page_delete_options($pages, &$table) {
        global $OUTPUT;
        foreach ($pages as $page) {
            $link = socialwiki_parser_link($page);
            $class = ($link['new']) ? 'class="socialwiki-newentry"' : "";
            $pagelink = '<a href="' . $link['url'] . '"' . $class . '>' .
                    format_string($link['content']) . ' (ID:' . $page->id . ')' . '</a>';
            $urledit = new moodle_url('/mod/socialwiki/edit.php', array('pageid' => $page->id, 'sesskey' => sesskey()));
            $urldelete = new moodle_url('/mod/socialwiki/admin.php', array(
                'pageid'  => $this->page->id,
                'delete'  => $page->id,
                'listall' => $this->listall,
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
        $PAGE->requires->js(new moodle_url("table/datatables.min.js"));
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
        Global $OUTPUT, $CFG, $USER;

        // USER INFO OUTPUT.
        $user = socialwiki_get_user_info($this->uid);
        $html = $OUTPUT->heading(fullname($user), 1, 'colourtext');
        $class = 'home-picture' . ($user->id == -1 ? ' unknown-user' : '');
        $html .= "<div class='$class'>";
        $html .= $OUTPUT->user_picture($user, array('size' => 100));
        $html .= "</div>";

        // PEER SCORES OUTPUT.
        $peer = socialwiki_peer::socialwiki_get_peer($user->id, $this->subwiki->id, $USER->id);
        $html .= $OUTPUT->container_start('peer-info colourtext');
        $table = new html_table();
        $table->head = array(get_string('peerscores', 'socialwiki'));
        $table->attributes['class'] = 'peer-table colourtext';
        $table->align = array('left');
        $table->data = array();

        // Result placed in table below.
        // Don't show related peer scores if user is viewing themselves or an unknown user.
        if ($USER->id != $user->id && $user->id != -1) {
            // Make button to follow/unfollow.
            if (!socialwiki_is_following($USER->id, $user->id, $this->subwiki->id) && $USER->id != $this->uid) {
                $icon = new moodle_url('/mod/socialwiki/pix/icons/follow.png');
                $text = get_string('follow', 'socialwiki');
                $tip = get_string('follow_tip', 'socialwiki');
            } else {
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
            $followbtn .= html_writer::tag('img', "", array('src' => $icon)) . $text;
            $followbtn .= html_writer::end_tag('button');
            $followbtn .= html_writer::end_tag('form');

            $row1 = new html_table_row(array(get_string('networkdistance', 'socialwiki').':', $peer->depth, $followbtn));
            $row1->cells[2]->rowspan = 3;

            $table->data[] = $row1;
            $table->data[] = array(get_string('followsim', 'socialwiki').':', $peer->followsim);
            $table->data[] = array(get_string('likesim', 'socialwiki').':', $peer->likesim);
            $table->data[] = array(get_string('popularity', 'socialwiki').':', $peer->popularity);
            $html .= html_writer::table($table);
        } else {
            $table->data[] = array(get_string('popularity', 'socialwiki').':', $peer->popularity);
            $html .= html_writer::table($table);
            $html .= "<br/><br/><br/>";
        }
        $html .= $OUTPUT->container_end();
        echo $html;

        // Favourites Table.
        socialwiki_table::builder($this->uid, $this->subwiki->id, 'userfaves');
        // User Versions Table.
        socialwiki_table::builder($this->uid, $this->subwiki->id, 'userpages');
    }
}

class page_socialwiki_help extends page_socialwiki {
    /**
     * Sets the URL of the page.
     */
    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/socialwiki/help.php', array('id' => $PAGE->cm->id));
    }

    /**
     * Add to the navigation bar at the top of the page.
     */
    public function create_navbar() {
        global $PAGE, $CFG;
        $PAGE->navbar->add(format_string($this->title), $CFG->wwwroot . '/mod/socialwiki/help.php?id=' . $PAGE->cm->id);
    }

    /**
     * Do not print the help button.
     */
    public function print_help() {
    }

    /**
     * Prints the page content.
     */
    public function print_content() {
        global $PAGE;
        echo $this->wikioutput->help_content('home');
        echo $this->wikioutput->help_content('search');
        echo $this->wikioutput->help_content('create');
        echo $this->wikioutput->help_content('edit');
        echo $this->wikioutput->help_content('versions');
        echo $this->wikioutput->help_content('diff');
        if (has_capability('mod/socialwiki:managewiki', context_module::instance($PAGE->cm->id))) {
            echo $this->wikioutput->help_content('admin');
        }
        echo $this->wikioutput->help_content('viewuserpages');
    }
}