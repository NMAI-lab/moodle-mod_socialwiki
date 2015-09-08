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
 * Moodle socialwiki Renderer.
 *
 * @package   mod_socialwiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * SocialWiki Renderer Class.
 *
 * @package   mod_socialwiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_socialwiki_renderer extends plugin_renderer_base {

    /**
     * Compares two pages.
     *
     * @param int $pageid
     * @param stdClass $old The first page to compare against.
     * @param stdClass $new The second page to compare against.
     * @return string
     */
    public function diff($pageid, $old, $new) {
        global $CFG;
        $page = socialwiki_get_page($pageid);
        $strdatetime = get_string('strftimedatetime', 'langconfig');

        // View old version link.
        $userlink = new moodle_url('/mod/socialwiki/viewuserpages.php',
                array('userid' => $old->user->id, 'subwikiid' => $page->subwikiid));
        $pagelink = new moodle_url('/mod/socialwiki/view.php', array('pageid' => $old->id));

        // Userinfo container.
        $oldheading = $this->output->container_start('socialwiki-diffright');
        $oldheading .= html_writer::link($userlink->out(false), fullname($old->user)) . ' '; // Username.
        $oldheading .= $this->output->user_picture($old->user, array('popup' => true)); // User picture.
        $oldheading .= $this->output->container_end();
        // Version number container.
        $oldheading .= $this->output->container_start('socialwiki-diffleft');
        $oldheading .= html_writer::link($pagelink->out(false), get_string('page') . ' ' . $old->id);
        $oldheading .= $this->output->container_end();
        // Userdate container.
        $oldheading .= $this->output->container_start('socialwiki-difftime');
        $oldheading .= userdate($old->timecreated, $strdatetime);
        $oldheading .= $this->output->container_end();

        // View new version link.
        $userlink = new moodle_url('/mod/socialwiki/viewuserpages.php',
                array('userid' => $new->user->id, 'subwikiid' => $page->subwikiid));
        $pagelink = new moodle_url('/mod/socialwiki/view.php', array('pageid' => $new->id));

        // New user info.
        $newheading = $this->output->container_start('socialwiki-diffleft');
        $newheading .= $this->output->user_picture($new->user, array('popup' => true)); // User picture.
        $newheading .= ' ' . html_writer::link($userlink->out(false), fullname($new->user)); // Username.
        $newheading .= $this->output->container_end();
        // Version.
        $newheading .= $this->output->container_start('socialwiki-diffright');
        $newheading .= html_writer::link($pagelink->out(false), get_string('page') . ' ' . $new->id);
        $newheading .= $this->output->container_end();
        // Userdate.
        $newheading .= $this->output->container_start('socialwiki-difftime');
        $newheading .= userdate($new->timecreated, $strdatetime);
        $newheading .= $this->output->container_end();

        $oldheading = $this->output->container($oldheading, array('class' => 'socialwiki-diffheading'));
        $newheading = $this->output->container($newheading, array('class' => 'socialwiki-diffheading'));

        $olddiff = $this->output->container($old->diff, array('class' => 'socialwiki-diffcontent'));
        $newdiff = $this->output->container($new->diff, array('class' => 'socialwiki-diffcontent'));

        $html = $this->output->container_start('socialwiki-clear');
        $html .= $this->output->container($oldheading . $olddiff, array('class' => 'socialwiki-diff'));
        $html .= $this->output->container($newheading . $newdiff, array('class' => 'socialwiki-diff'));
        $html .= $this->output->container_end();

        // Add the paging bars.
        $html .= $this->output->container_start('socialwiki-clear');
        $html .= $this->output->container($this->diff_paging_bar($old->id, "$CFG->wwwroot/mod/socialwiki/diff.php?pageid="
                . "$pageid&amp;comparewith=$new->id&amp;compare="), 'socialwiki-diffpaging');
        $html .= $this->output->container($this->diff_paging_bar($new->id, "$CFG->wwwroot/mod/socialwiki/diff.php?pageid="
                . "$pageid&amp;compare=$old->id&amp;comparewith="), 'socialwiki-diffpaging');
        $html .= $this->output->container_end();

        return $html;
    }

    /**
     * Prints a single paging bar to provide access to other versions.
     *
     * @param int $pageid The ID of the page being compared.
     * @param string $url The url for the diff.
     * @return string
     */
    public function diff_paging_bar($pageid, $url) {
        // Get all pages related to the page being compared.
        $relations = socialwiki_get_relations($pageid);
        // Get the index of the current page id in the array.
        $pageindex = socialwiki_indexof_page($pageid, $relations);
        $totalcount = count($relations) - 1;

        if ($pageindex == -1) {
            print_error('invalidparameters', 'socialwiki');
        }

        // If there is more than one page create html for paging bar.
        $html = "";
        if ($totalcount > 1) {
            $html .= '<div class="paging">';

            // Add first link to first page.
            if ($pageindex != 0) {
                // Print link to parent page.
                $html .= " <a href='$url{$relations[0]->id}'>{$relations[0]->id}</a> ";
                // Print link to page before current.
                if ($pageindex > 2) {
                    $html .= "... <a href='$url{$relations[$pageindex - 1]->id}'>{$relations[$pageindex - 1]->id}</a> ";
                } else if ($pageindex > 1) {
                    $html .= " <a href='$url{$relations[$pageindex - 1]->id}'>{$relations[$pageindex - 1]->id}</a> ";
                }
            }
            // Print current page.
            $html .= $pageid;
            if ($pageindex != $totalcount) {
                if ($pageindex < $totalcount - 2) {
                    $html .= " <a href='$url{$relations[$pageindex + 1]->id}'>{$relations[$pageindex + 1]->id}</a> ...";
                } else if ($pageindex < $totalcount - 1) {
                    $html .= " <a href='$url{$relations[$pageindex + 1]->id}'>{$relations[$pageindex + 1]->id}</a> ";
                }
                // Print last page in the array.
                $html .= " <a href='$url{$relations[$totalcount]->id}'>{$relations[$totalcount]->id}</a> ";
            }
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Information.
     *
     * @return string HTML
     */
    public function socialwiki_info() {
        global $PAGE;
        return $this->output->box(format_module_intro('socialwiki',
                $this->page->activityrecord, $PAGE->cm->id), 'generalbox', 'intro');
    }

    /**
     * Build the tabs for the pages.
     *
     * @param stdClass $page The current page.
     * @param string[] $tabitems The items in the tab.
     * @param array $options Includes the active tab and which are inactive.
     * @return string HTML
     */
    public function tabs($page, $tabitems, $options) {
        global $PAGE;
        $tabs = array();
        $context = context_module::instance($this->page->cm->id);

        $pageid = null;
        if (!empty($page)) {
            $pageid = $page->id;
        }

        $selected = $options['activetab'];

        if (!empty($options['inactivetabs'])) {
            $inactive = $options['inactivetabs'];
        } else {
            $inactive = array();
        }

        foreach ($tabitems as $tab) {
            if ($tab == 'edit' && !has_capability('mod/socialwiki:editpage', $context)) {
                continue;
            }
            if ($tab == 'comments' && !has_capability('mod/socialwiki:viewcomment', $context)) {
                continue;
            }
            if ($tab == 'files' && !has_capability('mod/socialwiki:viewpage', $context)) {
                continue;
            }
            if (($tab == 'view' || $tab == 'home' || $tab == 'versions') && !has_capability('mod/socialwiki:viewpage', $context)) {
                continue;
            }
            if ($tab == 'admin' && !has_capability('mod/socialwiki:managewiki', $context)) {
                continue;
            }

            $link = new moodle_url('/mod/socialwiki/' . $tab . '.php', array('pageid' => $pageid));
            if ($tab == 'home') {
                $link = new moodle_url('/mod/socialwiki/' . $tab . '.php', array('id' => $PAGE->cm->id));
            }

            if ($tab == 'versions') {
                $link = new moodle_url('/mod/socialwiki/versions.php', array('pageid' => $pageid));
            }

            $tabs[] = new tabobject($tab, $link, get_string($tab, 'socialwiki'));
        }

        return $this->tabtree($tabs, $selected, $inactive);
    }

    /**
     * Link to the printer friendly version.
     *
     * @param stdClass $page The current page.
     * @return string HTML
     */
    public function prettyview_link($page) {
        $link = new moodle_url('/mod/socialwiki/prettyview.php', array('pageid' => $page->id));
        $html = $this->output->container_start('socialwiki-right');
        $html .= $this->output->action_link($link, get_string('prettyprint', 'socialwiki'), new popup_action('click', $link));
        $html .= $this->output->container_end();
        return $html;
    }

    /**
     * Print the subwiki selector.
     *
     * @param stdClass $wiki The current wiki.
     * @param stdClass $subwiki The current subwiki.
     * @param stdClass $page The current page.
     * @param string $pagetype What tab is active right now.
     */
    public function socialwiki_print_subwiki_selector($wiki, $subwiki, $page, $pagetype = 'view') {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        switch ($pagetype) {
            case 'files':
                $baseurl = new moodle_url('/mod/socialwiki/files.php');
                break;
            case 'view':
            default:
                $baseurl = new moodle_url('/mod/socialwiki/view.php');
                break;
        }

        $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
        $context = context_module::instance($cm->id);
        // TODO: A plenty of duplicated code below this lines.
        // Create private functions.
        switch (groups_get_activity_groupmode($cm)) {
            case NOGROUPS:
                if ($wiki->wikimode == 'collaborative') {
                    // No need to print anything.
                    return;
                } else if ($wiki->wikimode == 'individual') {
                    // We have private wikis here.

                    $view = has_capability('mod/socialwiki:viewpage', $context);
                    $manage = has_capability('mod/socialwiki:managewiki', $context);

                    // Only people with these capabilities can view all wikis.
                    if ($view && $manage) {
                        // TODO: Print here a combo that contains all users.
                        $users = get_enrolled_users($context);
                        $options = array();
                        foreach ($users as $user) {
                            $options[$user->id] = fullname($user);
                        }

                        echo $this->output->container_start('socialwiki-right');
                        $params = array('wid' => $wiki->id, 'title' => $page->title);
                        if ($pagetype == 'files') {
                            $params['pageid'] = $page->id;
                        }
                        $baseurl->params($params);
                        $name = 'uid';
                        $selected = $subwiki->userid;
                        echo $this->output->single_select($baseurl, $name, $options, $selected);
                        echo $this->output->container_end();
                    }
                    return;
                } else {
                    // Error.
                    return;
                }
            case SEPARATEGROUPS:
                if ($wiki->wikimode == 'collaborative') {
                    // We need to print a select to choose a course group.
                    $params = array('wid' => $wiki->id, 'title' => $page->title);
                    if ($pagetype == 'files') {
                        $params['pageid'] = $page->id;
                    }
                    $baseurl->params($params);

                    echo $this->output->container_start('socialwiki-right');
                    groups_print_activity_menu($cm, $baseurl);
                    echo $this->output->container_end();
                    return;
                } else if ($wiki->wikimode == 'individual') {
                    // TODO: Print here a combo that contains all users of that subwiki.
                    $view = has_capability('mod/socialwiki:viewpage', $context);
                    $manage = has_capability('mod/socialwiki:managewiki', $context);

                    // Only people with these capabilities can view all wikis.
                    if ($view && $manage) {
                        $users = get_enrolled_users($context);
                        $options = array();
                        foreach ($users as $user) {
                            $groups = groups_get_all_groups($cm->course, $user->id);
                            if (!empty($groups)) {
                                foreach ($groups as $group) {
                                    $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                                }
                            } else {
                                $name = get_string('notingroup', 'socialwiki');
                                $options[0][$name]['0' . '-' . $user->id] = fullname($user);
                            }
                        }
                    } else {
                        $group = groups_get_group($subwiki->groupid);
                        if (!$group) {
                            return;
                        }
                        $users = groups_get_members($subwiki->groupid);
                        foreach ($users as $user) {
                            $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                        }
                    }
                    echo $this->output->container_start('socialwiki-right');
                    $params = array('wid' => $wiki->id, 'title' => $page->title);
                    if ($pagetype == 'files') {
                        $params['pageid'] = $page->id;
                    }
                    $baseurl->params($params);
                    $name = 'groupanduser';
                    $selected = $subwiki->groupid . '-' . $subwiki->userid;
                    echo $this->output->single_select($baseurl, $name, $options, $selected);
                    echo $this->output->container_end();

                    return;
                } else {
                    // Error.
                    return;
                }
            CASE VISIBLEGROUPS:
                if ($wiki->wikimode == 'collaborative') {
                    // We need to print a select to choose a course group
                    // moodle_url will take care of encoding for us.
                    $params = array('wid' => $wiki->id, 'title' => $page->title);
                    if ($pagetype == 'files') {
                        $params['pageid'] = $page->id;
                    }
                    $baseurl->params($params);

                    echo $this->output->container_start('socialwiki-right');
                    groups_print_activity_menu($cm, $baseurl);
                    echo $this->output->container_end();
                    return;
                } else if ($wiki->wikimode == 'individual') {
                    $users = get_enrolled_users($context);
                    $options = array();
                    foreach ($users as $user) {
                        $groups = groups_get_all_groups($cm->course, $user->id);
                        if (!empty($groups)) {
                            foreach ($groups as $group) {
                                $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                            }
                        } else {
                            $name = get_string('notingroup', 'socialwiki');
                            $options[0][$name]['0' . '-' . $user->id] = fullname($user);
                        }
                    }

                    echo $this->output->container_start('socialwiki-right');
                    $params = array('wid' => $wiki->id, 'title' => $page->title);
                    if ($pagetype == 'files') {
                        $params['pageid'] = $page->id;
                    }
                    $baseurl->params($params);
                    $name = 'groupanduser';
                    $selected = $subwiki->groupid . '-' . $subwiki->userid;
                    echo $this->output->single_select($baseurl, $name, $options, $selected);
                    echo $this->output->container_end();

                    return;
                } else {
                    // Error.
                    return;
                }
            default:
                // Error.
                return;
        }
    }

    /**
     * Builds the version view for search or pages.
     *
     * @param string $type Either versions or search.
     * @param array $options Parameters for the view menu links.
     * @param stdClass[] $pages An array of the pages to show.
     * @param int $currentview The current view.
     * @param int $swid The subwiki ID.
     * @param int $pid The page ID.
     */
    public function versions($type, $options, $pages, $currentview, $swid, $pid = -1) {
        global $USER, $CFG;
        $selectoptions = array();
        foreach (array('tree', 'list', 'popular') as $key => $v) {
            $selectoptions[$key] = get_string($v, 'socialwiki');
        }

        $select = new single_select(new moodle_url("/mod/socialwiki/$type.php", $options), 'view', $selectoptions, $currentview);
        $select->label = get_string('searchviews', 'socialwiki');

        echo $this->output->container($this->output->render($select), 'midpad');

        if ($currentview == 1) {
            require($CFG->dirroot . '/mod/socialwiki/table/table.php');
            echo socialwiki_versiontable::html_versiontable($USER->id, $swid, $pages, 'version');
        } else {
            require($CFG->dirroot . '/mod/socialwiki/tree/tree.php');
            $tree = new socialwiki_tree();
            $tree->build_tree($pages);
            $tree->display($pid);
        }
    }

    /**
     * Makes a new files tree.
     *
     * @param stdClass $context The current context.
     * @param stdClass $subwiki The current subwiki.
     * @return socialwiki_files_tree
     */
    public function socialwiki_files_tree($context, $subwiki) {
        return $this->render(new socialwiki_files_tree($context, $subwiki));
    }

    public function render_socialwiki_files_tree(socialwiki_files_tree $tree) {
        if (empty($tree->dir['subdirs']) && empty($tree->dir['files'])) {
            $html = $this->output->box(get_string('nofilesavailable', 'repository'));
        } else {
            $htmlid = 'socialwiki_files_tree_'.uniqid();
            $module = array('name' => 'mod_socialwiki', 'fullpath' => '/mod/socialwiki/module.js');
            $this->page->requires->js_init_call('M.mod_socialwiki.init_tree', array(false, $htmlid), false, $module);
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree, $tree->dir);
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Opens a content area.
     *
     * @return string HTML
     */
    public function content_area_begin() {
        $html = "";
        $html .= html_writer::start_div('socialwiki_wikicontent', array("id" => "socialwiki_content_area"));
        return $html;
    }

    /**
     * Closes a content area.
     *
     * @return string HTML
     */
    public function content_area_end() {
        $html = "";
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Outputs the main socialwiki view area, under the toolbar.
     *
     * @param string $pagetitle The page title.
     * @param string $pagecontent The page content.
     * @param stdClass $page The page itself.
     * @return string HTML
     */
    public function viewing_area($pagecontent, $page) {
        global $PAGE;

        $html = html_writer::start_div('wikipage');
        $html .= $pagecontent;
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('socialwiki-contributors');
        $html .= get_string('pagecontributors', 'socialwiki');
        $contributors = socialwiki_get_contributors($page->id);

        $contriblinks = "";

        foreach (array_reverse($contributors) as $contrib) {
            $user = socialwiki_get_user_info($contrib);
            $userlink = self::makeuserlink($user->id, $PAGE->cm->id, $page->subwikiid);
            // Pretend to list (to get them in chronological order).
            $contriblinks .= '<br/>' . html_writer::link($userlink->out(false), fullname($user));
        }

        $html .= $contriblinks;

        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Build a moodle_url to the user's profile.
     *
     * @param int $uid The user ID.
     * @param int $cmid The course module ID.
     * @param int $swid The subwiki ID.
     * @return moodle_url
     */
    public static function makeuserlink($uid, $cmid, $swid) {
        global $USER;
        if ($USER->id == $uid) {
            return new moodle_url('/mod/socialwiki/home.php', array('id' => $cmid));
        } else {
            return new moodle_url('/mod/socialwiki/viewuserpages.php', array('userid' => $uid, 'subwikiid' => $swid));
        }
    }

    /**
     * The help area content.
     *
     * @param string $heading The section heading.
     * @param string $type The section content type to show.
     * @return string HTML
     */
    public function help_content($type) {
        $html = "";
        $html .= html_writer::start_div("", array('id' => $type, 'style' => 'padding-top: 40px; margin-top: -20px'));
        $html .= html_writer::tag('h2', get_string($type, 'socialwiki'));
        $html .= get_string('help_' . $type, 'socialwiki');
        $html .= html_writer::end_div();
        return $html;
    }
}

/**
 * SocialWiki Files Tree Class.
 *
 * @package   mod_socialwiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class socialwiki_files_tree implements renderable {

    /**
     * The current context.
     *
     * @var stdClass
     */
    public $context;

    /**
     * The file tree data.
     *
     * @var string
     */
    public $dir;

    /**
     * The current subwiki.
     *
     * @var stdClass
     */
    public $subwiki;

    /**
     * Creates a new file tree.
     *
     * @param stdClass $context The current context.
     * @param stdClass $subwiki The current subwiki
     */
    public function __construct($context, $subwiki) {
        $fs = get_file_storage();
        $this->context = $context;
        $this->subwiki = $subwiki;
        $this->dir = $fs->get_area_tree($context->id, 'mod_socialwiki', 'attachments', $subwiki->id);
    }
}
