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
 * Moodle socialwiki 2.0 Renderer
 *
 * @package   mod-socialwiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_socialwiki_renderer extends plugin_renderer_base {
    public function page_index() {
        global $CFG;
        $html = '';
        // Checking wiki instance
        if (!$wiki = socialwiki_get_wiki($this->page->cm->instance)) {
            return false;
        }

        // @TODO: Fix call to socialwiki_get_subwiki_by_group
        $gid = groups_get_activity_group($this->page->cm);
        $gid = !empty($gid) ? $gid : 0;
        if (!$subwiki = socialwiki_get_subwiki_by_group($this->page->cm->instance, $gid)) {
            return false;
        }
        $swid = $subwiki->id;
        $pages = socialwiki_get_page_list($swid);
        $selectoptions = array();
        foreach ($pages as $page) {
            $selectoptions[$page->id] = format_string($page->title, true, array('context' => $this->page->context));
        }
        $label = get_string('pageindex', 'socialwiki') . ': ';
        $select = new single_select(new moodle_url('/mod/socialwiki/view.php'), 'pageid', $selectoptions);
        $select->label = $label;
        return $this->output->container($this->output->render($select), 'socialwiki_index');
    }

    public function search_result($records, $subwiki) {
        global $CFG, $PAGE;
        $table = new html_table();
        $context = context_module::instance($PAGE->cm->id);
        $strsearchresults = get_string('searchresult', 'socialwiki');
        $totalcount = count($records);
        $html = $this->output->heading("$strsearchresults $totalcount");
        foreach ($records as $page) {
            $table->head = array('title' => format_string($page->title) . ' (' . html_writer::link($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $page->id, get_string('view', 'socialwiki')) . ')');
            $table->align = array('title' => 'left');
            $table->width = '100%';
            $table->data = array(array(file_rewrite_pluginfile_urls(format_text($page->cachedcontent, FORMAT_HTML), 'pluginfile.php', $context->id, 'mod_socialwiki', 'attachments', $subwiki->id)));
            $table->colclasses = array('socialwikisearchresults');
            $html .= html_writer::table($table);
        }
        $html = html_writer::tag('div', $html, array('class'=>'no-overflow'));
        return $this->output->container($html);
    }

    public function diff($pageid, $old, $new, $options = array()) {
        global $CFG;
        if (!empty($options['total'])) {
            $total = $options['total'];
        } else {
            $total = 0;
        }
        $diff1 = format_text($old->diff, FORMAT_HTML, array('overflowdiv'=>true));
        $diff2 = format_text($new->diff, FORMAT_HTML, array('overflowdiv'=>true));
        $strdatetime = get_string('strftimedatetime', 'langconfig');

        $olduser = $old->user;
        $versionlink = new moodle_url('/mod/socialwiki/viewversion.php', array('pageid' => $pageid, 'versionid' => $old->id));
        $restorelink = new moodle_url('/mod/socialwiki/restoreversion.php', array('pageid' => $pageid, 'versionid' => $old->id));
        $userlink = new moodle_url('/user/view.php', array('id' => $olduser->id));
        // view version link
        $oldversionview = ' ';
        $oldversionview .= html_writer::link($versionlink->out(false), get_string('view', 'socialwiki'), array('class' => 'socialwiki_diffview'));
        $oldversionview .= ' ';
        // restore version link
        $oldversionview .= html_writer::link($restorelink->out(false), get_string('restore', 'socialwiki'), array('class' => 'socialwiki_diffview'));

        // userinfo container
        $oldheading = $this->output->container_start('socialwiki_diffuserleft');
        // username
        $oldheading .= html_writer::link($CFG->wwwroot . '/user/view.php?id=' . $olduser->id, fullname($olduser)) . '&nbsp;';
        // user picture
        $oldheading .= html_writer::link($userlink->out(false), $this->output->user_picture($olduser, array('popup' => true)), array('class' => 'notunderlined'));
        $oldheading .= $this->output->container_end();

        // version number container
        $oldheading .= $this->output->container_start('socialwiki_diffversion');
        $oldheading .= get_string('version') . ' ' . $old->version . $oldversionview;
        $oldheading .= $this->output->container_end();
        // userdate container
        $oldheading .= $this->output->container_start('socialwiki_difftime');
        $oldheading .= userdate($old->timecreated, $strdatetime);
        $oldheading .= $this->output->container_end();

        $newuser = $new->user;
        $versionlink = new moodle_url('/mod/socialwiki/viewversion.php', array('pageid' => $pageid, 'versionid' => $new->id));
        $restorelink = new moodle_url('/mod/socialwiki/restoreversion.php', array('pageid' => $pageid, 'versionid' => $new->id));
        $userlink = new moodle_url('/user/view.php', array('id' => $newuser->id));

        $newversionview = ' ';
        $newversionview .= html_writer::link($versionlink->out(false), get_string('view', 'socialwiki'), array('class' => 'socialwiki_diffview'));
        // new user info
        $newheading = $this->output->container_start('socialwiki_diffuserright');
        $newheading .= $this->output->user_picture($newuser, array('popup' => true));

        $newheading .= html_writer::link($userlink->out(false), fullname($newuser), array('class' => 'notunderlined'));
        $newheading .= $this->output->container_end();

        // version
        $newheading .= $this->output->container_start('socialwiki_diffversion');
        $newheading .= get_string('version') . '&nbsp;' . $new->version . $newversionview;
        $newheading .= $this->output->container_end();
        // userdate
        $newheading .= $this->output->container_start('socialwiki_difftime');
        $newheading .= userdate($new->timecreated, $strdatetime);
        $newheading .= $this->output->container_end();

        $oldheading = html_writer::tag('div', $oldheading, array('class'=>'socialwiki-diff-heading header clearfix'));
        $newheading = html_writer::tag('div', $newheading, array('class'=>'socialwiki-diff-heading header clearfix'));

        $html  = '';
        $html .= html_writer::start_tag('div', array('class'=>'socialwiki-diff-container clearfix'));
        $html .= html_writer::tag('div', $oldheading.$diff1, array('class'=>'socialwiki-diff-leftside'));
        $html .= html_writer::tag('div', $newheading.$diff2, array('class'=>'socialwiki-diff-rightside'));
        $html .= html_writer::end_tag('div');

        if (!empty($total)) {
            $html .= '<div class="socialwiki_diff_paging">';
            $html .= $this->output->container($this->diff_paging_bar(1, $new->version - 1, $old->version, $CFG->wwwroot . '/mod/socialwiki/diff.php?pageid=' . $pageid . '&amp;comparewith=' . $new->version . '&amp;', 'compare', false, true), 'socialwiki_diff_oldpaging');
            $html .= $this->output->container($this->diff_paging_bar($old->version + 1, $total, $new->version, $CFG->wwwroot . '/mod/socialwiki/diff.php?pageid=' . $pageid . '&amp;compare=' . $old->version . '&amp;', 'comparewith', false, true), 'socialwiki_diff_newpaging');
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Prints a single paging bar to provide access to other versions
     *
     * @param int $minpage First page to be displayed in the bar
     * @param int $maxpage Last page to be displayed in the bar
     * @param int $page The page you are currently viewing
     * @param mixed $baseurl If this  is a string then it is the url which will be appended with $pagevar, an equals sign and the page number.
     *                          If this is a moodle_url object then the pagevar param will be replaced by the page no, for each page.
     * @param string $pagevar This is the variable name that you use for the page number in your code (ie. 'tablepage', 'blogpage', etc)
     * @param bool $nocurr do not display the current page as a link
     * @param bool $return whether to return an output string or echo now
     * @return bool or string
     */
    public function diff_paging_bar($minpage, $maxpage, $page, $baseurl, $pagevar = 'page', $nocurr = false) {
        $totalcount = $maxpage - $minpage;
        $maxdisplay = 2;
        $html = '';

        if ($totalcount > 0) {
            $html .= '<div class="paging">';
            $html .= get_string('version', 'socialwiki') . ':';
            if ($page - $minpage > 0) {
                $pagenum = $page - 1;
                if (!is_a($baseurl, 'moodle_url')) {
                    $html .= '&nbsp;(<a class="previous" href="' . $baseurl . $pagevar . '=' . $pagenum . '">' . get_string('previous') . '</a>)&nbsp;';
                } else {
                    $html .= '&nbsp;(<a class="previous" href="' . $baseurl->out(false, array($pagevar => $pagenum)) . '">' . get_string('previous') . '</a>)&nbsp;';
                }
            }

            if ($page - $minpage > 4) {
                $startpage = $page - 3;
                if (!is_a($baseurl, 'moodle_url')) {
                    $html .= '&nbsp;<a href="' . $baseurl . $pagevar . '=' . $minpage . '">' . $minpage . '</a>&nbsp;...';
                } else {
                    $html .= '&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $minpage)) . '">' . $minpage . '</a>&nbsp;...';
                }
            } else {
                $startpage = $minpage;
            }
            $currpage = $startpage;
            $displaycount = 0;
            while ($displaycount < $maxdisplay and $currpage <= $maxpage) {
                if ($page == $currpage && empty($nocurr)) {
                    $html .= '&nbsp;&nbsp;' . $currpage;
                } else {
                    if (!is_a($baseurl, 'moodle_url')) {
                        $html .= '&nbsp;&nbsp;<a href="' . $baseurl . $pagevar . '=' . $currpage . '">' . $currpage . '</a>';
                    } else {
                        $html .= '&nbsp;&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $currpage)) . '">' . $currpage . '</a>';
                    }

                }
                $displaycount++;
                $currpage++;
            }
            if ($currpage < $maxpage) {
                if (!is_a($baseurl, 'moodle_url')) {
                    $html .= '&nbsp;...<a href="' . $baseurl . $pagevar . '=' . $maxpage . '">' . $maxpage . '</a>&nbsp;';
                } else {
                    $html .= '&nbsp;...<a href="' . $baseurl->out(false, array($pagevar => $maxpage)) . '">' . $maxpage . '</a>&nbsp;';
                }
            } else if ($currpage == $maxpage) {
                if (!is_a($baseurl, 'moodle_url')) {
                    $html .= '&nbsp;&nbsp;<a href="' . $baseurl . $pagevar . '=' . $currpage . '">' . $currpage . '</a>';
                } else {
                    $html .= '&nbsp;&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $currpage)) . '">' . $currpage . '</a>';
                }
            }
            $pagenum = $page + 1;
            if ($page != $maxpage) {
                if (!is_a($baseurl, 'moodle_url')) {
                    $html .= '&nbsp;&nbsp;(<a class="next" href="' . $baseurl . $pagevar . '=' . $pagenum . '">' . get_string('next') . '</a>)';
                } else {
                    $html .= '&nbsp;&nbsp;(<a class="next" href="' . $baseurl->out(false, array($pagevar => $pagenum)) . '">' . get_string('next') . '</a>)';
                }
            }
            $html .= '</div>';
        }

        return $html;
    }
    public function socialwiki_info() {
        global $PAGE;
        return $this->output->box(format_module_intro('socialwiki', $this->page->activityrecord, $PAGE->cm->id), 'generalbox', 'intro');
    }

    public function tabs($page, $tabitems, $options) {
        $tabs = array();
        $context = context_module::instance($this->page->cm->id);

        $pageid = null;
        if (!empty($page)) {
            $pageid = $page->id;
        }

        $selected = $options['activetab'];

        // make specific tab linked even it is active
        if (!empty($options['linkedwhenactive'])) {
            $linked = $options['linkedwhenactive'];
        } else {
            $linked = '';
        }

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
            if (($tab == 'view' || $tab == 'map' || $tab == 'history') && !has_capability('mod/socialwiki:viewpage', $context)) {
                continue;
            }
            if ($tab == 'admin' && !has_capability('mod/socialwiki:managewiki', $context)) {
                continue;
            }
            $link = new moodle_url('/mod/socialwiki/'. $tab. '.php', array('pageid' => $pageid));
            if ($linked == $tab) {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'socialwiki'), '', true);
            } else {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'socialwiki'));
            }
        }

        return $this->tabtree($tabs, $selected, $inactive);
    }

    public function prettyview_link($page) {
        $html = '';
        $link = new moodle_url('/mod/socialwiki/prettyview.php', array('pageid' => $page->id));
        $html .= $this->output->container_start('socialwiki_right');
        $html .= $this->output->action_link($link, get_string('prettyprint', 'socialwiki'), new popup_action('click', $link));
        $html .= $this->output->container_end();
        return $html;
    }

    public function socialwiki_print_subwiki_selector($wiki, $subwiki, $page, $pagetype = 'view') {
        global $CFG, $USER;
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
        // @TODO: A plenty of duplicated code below this lines.
        // Create private functions.
        switch (groups_get_activity_groupmode($cm)) {
        case NOGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // No need to print anything
                return;
            } else if ($wiki->wikimode == 'individual') {
                // We have private wikis here

                $view = has_capability('mod/socialwiki:viewpage', $context);
                $manage = has_capability('mod/socialwiki:managewiki', $context);

                // Only people with these capabilities can view all wikis
                if ($view && $manage) {
                    // @TODO: Print here a combo that contains all users.
                    $users = get_enrolled_users($context);
                    $options = array();
                    foreach ($users as $user) {
                        $options[$user->id] = fullname($user);
                    }

                    echo $this->output->container_start('socialwiki_right');
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
                // error
                return;
            }
        case SEPARATEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // We need to print a select to choose a course group

                $params = array('wid'=>$wiki->id, 'title'=>$page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);

                echo $this->output->container_start('socialwiki_right');
                groups_print_activity_menu($cm, $baseurl);
                echo $this->output->container_end();
                return;
            } else if ($wiki->wikimode == 'individual') {
                //  @TODO: Print here a combo that contains all users of that subwiki.
                $view = has_capability('mod/socialwiki:viewpage', $context);
                $manage = has_capability('mod/socialwiki:managewiki', $context);

                // Only people with these capabilities can view all wikis
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
                echo $this->output->container_start('socialwiki_right');
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
                // error
                return;
            }
        CASE VISIBLEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // We need to print a select to choose a course group
                // moodle_url will take care of encoding for us
                $params = array('wid'=>$wiki->id, 'title'=>$page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);

                echo $this->output->container_start('socialwiki_right');
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

                echo $this->output->container_start('socialwiki_right');
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
                // error
                return;
            }
        default:
            // error
            return;

        }

    }

    function menu_map($pageid, $currentselect) {
        $options = array('contributions', 'links', 'orphaned', 'pageindex', 'pagelist', 'updatedpages');
        $items = array();
        foreach ($options as $opt) {
            $items[] = get_string($opt, 'socialwiki');
        }
        $selectoptions = array();
        foreach ($items as $key => $item) {
            $selectoptions[$key + 1] = $item;
        }
        $select = new single_select(new moodle_url('/mod/socialwiki/map.php', array('pageid' => $pageid)), 'option', $selectoptions, $currentselect);
        $select->label = get_string('mapmenu', 'socialwiki') . ': ';
        return $this->output->container($this->output->render($select), 'midpad');
    }
    public function socialwiki_files_tree($context, $subwiki) {
        return $this->render(new socialwiki_files_tree($context, $subwiki));
    }
    public function render_socialwiki_files_tree(socialwiki_files_tree $tree) {
        if (empty($tree->dir['subdirs']) && empty($tree->dir['files'])) {
            $html = $this->output->box(get_string('nofilesavailable', 'repository'));
        } else {
            $htmlid = 'socialwiki_files_tree_'.uniqid();
            $module = array('name'=>'mod_socialwiki', 'fullpath'=>'/mod/socialwiki/module.js');
            $this->page->requires->js_init_call('M.mod_socialwiki.init_tree', array(false, $htmlid), false, $module);
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree, $tree->dir);
            $html .= '</div>';
        }
        return $html;
    }

    function menu_admin($pageid, $currentselect) {
        $options = array('removepages', 'deleteversions');
        $items = array();
        foreach ($options as $opt) {
            $items[] = get_string($opt, 'socialwiki');
        }
        $selectoptions = array();
        foreach ($items as $key => $item) {
            $selectoptions[$key + 1] = $item;
        }
        $select = new single_select(new moodle_url('/mod/socialwiki/admin.php', array('pageid' => $pageid)), 'option', $selectoptions, $currentselect);
        $select->label = get_string('adminmenu', 'socialwiki') . ': ';
        return $this->output->container($this->output->render($select), 'midpad');
    }


	//Outputs the html for the socialwiki navbar
	public function pretty_navbar($pageid)
	{
		global $CFG,$PAGE,$USER;
                
                $page = socialwiki_get_page($pageid);
                
		$html  = '';
		$html .= html_writer::start_div('', array('id' => 'socialwiki_nav'));
		$html .= html_writer::start_div('', array('id' => 'socialwiki_container'));

		//Back/Forward buttons
		$html .= html_writer::start_div('', array('id' => 'socialwiki_bfbuttons'));
		$html .= html_writer::start_tag('ul', array('id' => 'socialwiki_bflist', 'class' => 'horizontal_list'));
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link('','', array('id' => 'socialwiki_forwardbutton'));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link('','', array('id' => 'socialwiki_backbutton'));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::end_tag('ul');
		$html .= html_writer::end_div();

		//Page navigation buttons
		$html .= html_writer::start_div('', array('id' => 'socialwiki_navbuttons'));
		$html .= html_writer::start_tag('ul', array('id' => 'socialwiki_navlist', 'class' => 'horizontal_list'));
                
                $html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/view.php?pageid='.socialwiki_get_first_page($page->subwikiid)->id,'', array('id' => 'socialwiki_homebutton'));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
                
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/view.php?pageid='.$pageid,'', array('id' => 'socialwiki_viewbutton'));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/edit.php?pageid='.$pageid,'', array('id' => 'socialwiki_editbutton'));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link('','', array('id' => 'socialwiki_versionbutton'));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('ul');
		$html .= html_writer::end_div();

	
		//Search box
                $html .=  '<div id="socialwiki_search">
                    <form id="socialwiki_searchform">
                        <input id="socialwiki_searchbox" type="text" value="Search..."></input>
                    </form>
                </div>';	

		//Social buttons
		$html .= html_writer::start_div('', array('id' => 'socialwiki_socialbuttons'));
		$html .= html_writer::start_tag('ul', array('id' => 'socialwiki_socialbuttons', 'class' => 'horizontal_list'));
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');

		if (socialwiki_liked($USER->id, $pageid))
		{
			$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/like.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('id' => 'socialwiki_likebutton', 'like' =>'no'));
		}else
		{
			$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/like.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('id' => 'socialwiki_likebutton', 'like' =>'yes'));
		}
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$userto = socialwiki_get_author($pageid);
		if (socialwiki_is_following($USER->id,$userto->userid))
		{
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/follow.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('id' => 'socialwiki_friendbutton',  'friend' => 'no'));
		}
		else
		{
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/follow.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('id' => 'socialwiki_friendbutton',  'friend' => 'yes'));
		}
		$html .= html_writer::end_tag('li');
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::link('','', array('id' => 'socialwiki_managebutton'));
		$html .= html_writer::end_tag('li');
                
                $html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/comments.php?pageid='.$pageid,'', array('id' => 'socialwiki_commentsbutton'));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::end_tag('ul');
		$html .= html_writer::end_div();
		

		$html .= html_writer::end_div();
		$html .= html_writer::end_div();
		
		return $html;	
	}
        
        public function content_area_begin()
        {
                $html = '';
                $html .= html_writer::start_div('wikicontent');
                return $html;
        }

        public function content_area_end()
        {
                $html = '';
                $html .= html_writer::end_div();
                return $html;
        }

        
        //Outputs the main socialwiki view area, under the toolbar
        public function viewing_area($pagetitle, $pagecontent, $page)
        {
                global $PAGE,$USER;

                $html = '';
                
                $html .= $this->content_area_begin();
                $html .= html_writer::start_div('wikipage');
                $html .= html_writer::start_div('wikititle');
                $html .= html_writer::tag('h1', $pagetitle);
                
                $user = socialwiki_get_user_info($page->userid);
		$userlink = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $PAGE->cm->course));
                $html.=html_writer::link($userlink->out(false),fullname($user));

                
                $html .= html_writer::end_div();
                $html .= html_writer::start_div('', array('id' => 'wikicontent'));
                $html .= $pagecontent;
                $html .= html_writer::end_div();
                $html .= html_writer::end_div();
                $html .= $this->content_area_end();
                return $html;
        }
        
    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(), $subdir['dirname'], 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.s($subdir['dirname']).'</div> '.$this->htmllize_tree($tree, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$tree->context->id.'/mod_socialwiki/attachments/' . $tree->subwiki->id . '/'. $file->get_filepath() . $file->get_filename(), true);
            $filename = $file->get_filename();
            $image = $this->output->pix_icon(file_file_icon($file), $filename, 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.html_writer::link($url, $filename).'</div></li>';
        }
        $result .= '</ul>';

        return $result;
    }
}

class socialwiki_files_tree implements renderable {
    public $context;
    public $dir;
    public $subwiki;
    public function __construct($context, $subwiki) {
        $fs = get_file_storage();
        $this->context = $context;
        $this->subwiki = $subwiki;
        $this->dir = $fs->get_area_tree($context->id, 'mod_socialwiki', 'attachments', $subwiki->id);
    }
}