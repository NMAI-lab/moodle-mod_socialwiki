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
 * @package   mod_socialwiki
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_wiki_activity_task
 */

/**
 * Define the complete wiki structure for backup, with file and id annotations
 */
class backup_socialwiki_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $wiki = new backup_nested_element('socialwiki', array('id'), array('name',
            'intro', 'introformat', 'timecreated', 'timemodified', 'firstpagetitle',
            'wikimode', 'defaultformat', 'forceformat', 'editbegin', 'editend'));

        $subwikis = new backup_nested_element('subwikis');

        $subwiki = new backup_nested_element('subwiki', array('id'), array('groupid', 'userid'));

        $pages = new backup_nested_element('pages');

        $page = new backup_nested_element('page', array('id'), array('title', 'content',
            'format', 'timecreated', 'userid', 'pageviews', 'parent'));

        $likes = new backup_nested_element('likes');

        $like = new backup_nested_element('like', array('id'), array('userid', 'pageid'));

        $follows = new backup_nested_element('follows');

        $follow = new backup_nested_element('follow', array('id'), array('userfromid', 'usertoid'));

        $tags = new backup_nested_element('tags');

        $tag = new backup_nested_element('tag', array('id'), array('name', 'rawname'));

        // Build the tree.
        $wiki->add_child($subwikis);
        $subwikis->add_child($subwiki);

        $subwiki->add_child($pages);
        $pages->add_child($page);

        $subwiki->add_child($likes);
        $likes->add_child($like);

        $subwiki->add_child($follows);
        $follows->add_child($follow);

        $page->add_child($tags);
        $tags->add_child($tag);

        // Define sources.
        $wiki->set_source_table('socialwiki', array('id' => backup::VAR_ACTIVITYID));

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $subwiki->set_source_sql('
                SELECT *
                  FROM {socialwiki_subwikis}
                 WHERE wikiid = ?', array(backup::VAR_PARENTID));

            $page->set_source_table('socialwiki_pages', array('subwikiid' => backup::VAR_PARENTID));

            $like->set_source_table('socialwiki_likes', array('subwikiid' => backup::VAR_PARENTID));

            $follow->set_source_table('socialwiki_follows', array('subwikiid' => backup::VAR_PARENTID));

            $tag->set_source_sql('SELECT t.id, t.name, t.rawname
                                    FROM {tag} t
                                    JOIN {tag_instance} ti ON ti.tagid = t.id
                                   WHERE ti.itemtype = ?
                                     AND ti.itemid = ?', array(
                backup_helper::is_sqlparam('socialwiki_pages'),
                backup::VAR_PARENTID));
        }

        // Define id annotations.
        $subwiki->annotate_ids('group', 'groupid');

        $subwiki->annotate_ids('user', 'userid');

        $page->annotate_ids('user', 'userid');

        // Define file annotations.
        $wiki->annotate_files('mod_socialwiki', 'intro', null); // This file area hasn't itemid.
        $page->annotate_files('mod_socialwiki', 'attachments', 'id'); // This file area hasn't itemid.
        // Return the root element (wiki), wrapped into standard activity structure.
        return $this->prepare_activity_structure($wiki);
    }

}
