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
 * The user table for showing the wiki users.
 *
 * @package    mod_socialwiki
 * @copyright  2015 NMAI-lab
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * User Table Class.
 *
 * @package    mod_socialwiki
 * @copyright  2015 NMAI-lab
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usertable extends socialwiki_table {

    /**
     * Create a topic table.
     *
     * @param int $uid The current uid (userid).
     * @param int $swid The current subwikiid.
     * @param array $ids The list of user ID's.
     * @param string $type Table header options.
     */
    public function __construct($uid, $swid, $ids, $type) {
        parent::__construct($uid, $swid, $type);
        $this->userlist = $ids;
    }

    /**
     * Generate a table of all the users other than 'me'.
     *
     * @param int $me The current user's ID.
     * @param int $swid The current subwiki ID.
     * @return \usertable
     */
    public static function all_usertable($me, $swid) {
        $uids = socialwiki_get_active_subwiki_users($swid);
        $ids = array_filter($uids, function($i) use ($me) {
            return ($i != $me);
        });

        return new usertable($me, $swid, $ids, 'user');
    }

    /**
     * Generate a table of who the current user follows.
     *
     * @param int $uid The current user's ID.
     * @param int $swid The current subwiki ID.
     * @return \usertable
     */
    public static function followed_usertable($uid, $swid) {
        $uids = socialwiki_get_follows($uid, $swid);
        $ids = array_keys($uids);
        if (empty($ids)) {
            return null;
        }

        return new usertable($uid, $swid, $ids, 'user');
    }

    /**
     * Generate a table of users that follow the current user.
     *
     * @param int $uid The current user's ID.
     * @param int $swid The current subwiki ID.
     * @return \usertable
     */
    public static function followers_usertable($uid, $swid) {
        $ids = socialwiki_get_follower_users($uid, $swid);
        if (empty($ids)) {
            return null;
        }
        return new usertable($uid, $swid, $ids, 'user');
    }

    /**
     * Build the table data structure.
     *
     * @return array $table Each row being an array of head=>value pairs
     */
    public function get_table_data() {
        Global $CFG;

        $ids = $this->userlist;
        $headers = $this->headers;
        $me = $this->uid;
        $swid = $this->swid;
        $www = $CFG->wwwroot;

        // Define function to build a row from a user.
        $buildfunction = function ($id) use ($headers, $me, $swid, $www) {
            $user = socialwiki_get_user_info($id);
            $name = "<a style='margin:0;' class='socialwiki_link' href='"
                    . $www . "/mod/socialwiki/viewuserpages.php?userid="
                    . $user->id . "&subwikiid=$swid'>" . fullname($user) . "</a>";

            $peer = peer::socialwiki_get_peer($id, $swid, $me);
            switch ($peer->depth) {
                case 0:
                    $following = "Not in your network";
                    break;
                case 1:
                    $following = "Followed";
                    break;
                case 2:
                    $following = "Second Connection";
                    break;
                default:
                    $following = "Distant Connection";
                    break;
            }

            $rowdata = array(
                'name' => $name,
                'popularity' => $peer->popularity,
                'likesim' => substr("$peer->likesim", 0, 4),
                'followsim' => substr("$peer->followsim", 0, 4),
                'networkdistance' => $following
            );

            foreach ($headers as $key) {
                $row[$key] = $rowdata[$key];
            }

            return $row;
        };

        $tabledata = array_map($buildfunction, $ids); // End array_map.

        return $tabledata;
    }
}