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

class usertable extends socialwiki_table {

    public function __construct($uid, $swid, $ids, $headers) {
        parent::__construct($uid, $swid, $headers);
        $this->userlist = $ids;
    }

    /*
     * Template to create a table using a select/project (select A where B):
     * 1: use a specific DB access function to retrieve a subset of the users,
     *    it may be a superset of what we ultimately want.
     *    Examples:
     * 	  - get all users
     * 	  - get followers of some user
     *
     * 2: refine select condition by applying a filter
     *
     * 3: project by passing a subset of the headers with the make_table method.
     *    Examples:
     *    (all headers)
     *    $h = array("name", "distance","popularity", "likesim", "followsim");
     *    (just name and popularity)
     *    $h = array("name", popularity");
     */

    /**
     * returns all users except 'me'
     */
    public static function all_usertable($me, $swid) {
        $uids = socialwiki_get_active_subwiki_users($swid);
        $ids = array_filter($uids, function($i) use ($me) {
            return ($i != $me);
        });

        return new usertable($me, $swid, $ids, 'user');
    }

    /**
     * returns a usertable with all users I follow
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
     * returns a usertable with all my followers
     */
    public static function followers_usertable($uid, $swid) {
        $ids = socialwiki_get_follower_users($uid, $swid);
        if (empty($ids)) {
            return null;
        }
        return new usertable($uid, $swid, $ids, 'user');
    }

    /**
     * build the table data structure as an array of rows, each row being a head=>value pair
     * the rows are cxonstructed from the given user ids
     * the heads are taken from the given headers list
     * @param $ids a list of user ids
     * @param $headers an array of strings among: "name", "distance", "popularity", "likesim", "followsim"
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
                    . $user->id . "&subwikiid=" . $swid . "'>" . fullname($user) . "</a>";

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
                get_string('name', 'socialwiki') => $name,
                get_string('popularity', 'socialwiki') => $peer->popularity,
                get_string('likesim', 'socialwiki') => substr("$peer->likesim", 0, 4),
                get_string('followsim', 'socialwiki') => substr("$peer->followsim", 0, 4),
                get_string('networkdistance', 'socialwiki') => $following
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