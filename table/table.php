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
 * The standard table.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

Global $CFG;
require_once($CFG->dirroot . '/mod/socialwiki/table/usertable.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/topictable.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/versiontable.php');
require_once($CFG->dirroot . '/mod/socialwiki/peer.php');

/**
 * Table Class.
 *
 * Never used by itself. Extended by Topic, User and Version Tables.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class socialwiki_table {

    /**
     * The user ID.
     *
     * @var int
     */
    protected $uid;

    /**
     * The subwiki ID.
     *
     * @var int
     */
    protected $swid;

    /**
     * Array of headers for the table.
     *
     * @var string[]
     */
    protected $headers;

    /**
     * The table type.
     *
     * @var string[]
     */
    protected $tabletype;
    
    /**
     * Create a table.
     *
     * @param int $u The current user ID.
     * @param int $s The current subwiki ID.
     * @param string $h Table header options.
     */
    public function __construct($u, $s, $h, $v = ' typeg') {
        $this->uid = $u;
        $this->swid = $s;
        $this->headers = self::get_headers($h);
        $this->tabletype = $v;
    }

    /**
     * Used to get the table data.
     */
    abstract protected function get_table_data();

    /**
     * Prints the table in HTML format.
     *
     * @return string
     */
    public function print_html() {
        $output = "<table class='datatable{$this->tabletype}' width='100%'>";
        $tabledata = $this->get_table_data();
        // Headers.
        $output .= "<thead><tr>";
        foreach ($this->headers as $h) {
            $output .= "<th title='" . get_string($h.'_help', 'socialwiki') . "'>" . get_string($h, 'socialwiki') . "</th>";
        }
        $output .= "</tr></thead><tbody>";

        foreach ($tabledata as $row) {
            $output .= "<tr>";
            foreach ($row as $k => $val) {
                $output .= "<td>$val</td>";
            }
            $output .= "</tr>";
        }

        $output .= "</tbody></table>";
        return $output;
    }

    /**
     * Gets the correct headers for the table.
     *
     * @param string $type The type of table.
     * @return string array
     */
    public static function get_headers($type) {
        switch ($type) {
            case 'version':
                return array(
                    'title',
                    'contributors',
                    'updated',
                    'likes',
                    'views',
                    'favourite',
                    'popularity',
                    'likesim',
                    'followsim',
                    'networkdistance'
                );
            case 'mystuff':
                return array(
                    'title',
                    'contributors',
                    'updated',
                    'likes',
                    'views',
                    'favourite'
                );
            case 'topics':
                return array(
                    'title',
                    'versions',
                    'views',
                    'likes'
                );
            case 'user':
                return array(
                    'name',
                    'popularity',
                    'likesim',
                    'followsim',
                    'networkdistance'
                );
            default:
                return array('error in get_headers: ' . $type);
        }
    }

    /**
     * Builds any given table.
     *
     * @param int $userid The user's ID.
     * @param int $swid The current subwiki ID.
     * @param string $tabletype The table type to build.
     * @return string HTML
     */
    public static function builder($userid, $swid, $tabletype) {
        $trustcombiner = 'max';  // Default for now, should remove entirely.

        $t = null;
        switch ($tabletype) {
            case "mylikes":       // User likes.
                $t = socialwiki_versiontable::likes_versiontable($userid, $swid, $trustcombiner);
                break;
            case "myfaves":       // User favourites.
            case "userfaves":     // Favourites by another user.
                $t = socialwiki_versiontable::favourites_versiontable($userid, $swid, $trustcombiner);
                break;
            case "mypages":       // User pages.
            case "userpages":     // Pages by another user.
                $t = socialwiki_versiontable::user_versiontable($userid, $swid, $trustcombiner);
                break;
            case "pagesfollowed": // Versions by followed users.
                $t = socialwiki_versiontable::followed_versiontable($userid, $swid, $trustcombiner);
                break;
            case "newpages":      // New versions.
                $t = socialwiki_versiontable::new_versiontable($userid, $swid, $trustcombiner);
                break;
            case "allpages":      // All versions.
                $t = socialwiki_versiontable::all_versiontable($userid, $swid, $trustcombiner);
                break;
            case "followedusers": // Followed users.
                $t = socialwiki_usertable::followed_usertable($userid, $swid);
                break;
            case "followers":     // Followers.
                $t = socialwiki_usertable::followers_usertable($userid, $swid);
                break;
            case "allusers":      // All users.
                $t = socialwiki_usertable::all_usertable($userid, $swid);
                break;
            case "alltopics":     // All pages (grouped versions).
                $t = socialwiki_topictable::all_topictable($userid, $swid);
                break;
            default:
                $tabletype = 'unknowntabletype ' . $tabletype;
        }
        echo '<h2>'.get_string($tabletype, 'socialwiki').'</h2>';
        if ($t != null) {
            echo $t->print_html();
        } else {
            echo get_string($tabletype . '_empty', 'socialwiki');
        }
    }
}