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
 * The topic table for page groups.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Topic Table Class.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class socialwiki_topictable extends socialwiki_table {

    /**
     * The list of topics.
     *
     * @var array
     */
    private $tlist;

    /**
     * Create a topic table.
     *
     * @param int $uid The current uid (userid).
     * @param int $swid The current subwikiid.
     * @param list $list Description
     * @param string $type Table header options.
     */
    public function __construct($uid, $swid, $list, $type) {
        parent::__construct($uid, $swid, $type);
        $this->tlist = $list;
    }

    /**
     * Generate an all topics table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @return \socialwiki_topictable
     */
    public static function all_topictable($uid, $swid) {
        $topics = socialwiki_get_topics($swid);

        if (empty($topics)) {
            return null;
        }

        return new socialwiki_topictable($uid, $swid, $topics, 'topics');
    }

    /**
     * Build the table data structure.
     *
     * @return array $table Each row being an array of head=>value pairs
     */
    protected function get_table_data() {
        Global $PAGE;

        $table = array();

        foreach ($this->tlist as $title => $data) {
            $titlelink = '<a href="search.php?searchstring=' . $title
                    . '&id=' . $PAGE->cm->id . '&exact=1&option=1">' . $title . '</a>';

            $row = array(
                'title' => $titlelink,
                'versions' => $data["Versions"],
                'views' => $data["Views"],
                'likes' => $data["Likes"],
            );

            $table[] = $row; // Add row to table.
        }

        return $table;
    }
}
