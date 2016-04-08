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
 * Peers used for social data.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once("$CFG->dirroot/mod/socialwiki/locallib.php");

/**
 * Class that describes the similarity between the current user and another student in the activity.
 *
 * @copyright 2015 NMAI-lab
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class socialwiki_peer {

    /**
     * Trust indicator value = 1/distance or 0.
     *
     * @var int
     */
    public $trust = 0;

    /**
     * The user ID.
     *
     * @var int
     */
    public $id;

    /**
     * The similarity between likes of the peer and user.
     *
     * @var int
     */
    public $likesim = 0;

    /**
     * The similarity between the people the user and peer are following.
     *
     * @var int
     */
    public $followsim = 0;

    /**
     * Percent popularity.
     *
     * @var int
     */
    public $popularity;

    /**
     * Social distance: 1 for I'm following this user, 2 for friend of a friend, etc.
     *
     * @var int
     */
    public $depth;

    /**
     * Creates a new peer.
     *
     * @param array $arr Data for the peer.
     */
    public function __construct($arr) {
        $this->id = $arr['id'];
        $this->likesim = $arr['likesim'];
        $this->followsim = $arr['followsim'];
        $this->popularity = $arr['popularity'];
        $this->depth = $arr['depth'];
    }

    /**
     * Creates a new peer and computes its trust indicators.
     *
     * @param int $id A user ID.
     * @param int $swid The subwiki ID.
     * @param int $currentuser The current user ID.
     * @return peer
     */
    public static function make_with_indicators($id, $swid, $currentuser) {
        $newpeer = new socialwiki_peer(array('id' => $id, 'likesim' => 0, 'followsim' => 0, 'popularity' => 0, 'depth' => 0));

        if ($id == $currentuser) {
            $newpeer->depth = -1;
            $newpeer->trust = 1;
            $newpeer->followsim = 1;
        } else {
            $newpeer->compute_depth($currentuser, $swid);
            if ($newpeer->depth == 0) {
                $newpeer->trust = 0;
            } else {
                $newpeer->trust = 1 / $newpeer->depth;
            }
            $newpeer->set_follow_sim($currentuser, $swid);
            $newpeer->set_like_sim($currentuser, $swid);
        }
        $newpeer->popularity = socialwiki_get_followers($id, $swid); // Not dividing.

        return $newpeer;
    }

    /**
     * Calculates how far away another user is in your network.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     */
    private function compute_depth($uid, $swid) {
        $this->depth = socialwiki_follow_depth($uid, $this->id, $swid);
    }

    /**
     * Returns an array of the peer data.
     *
     * @return array
     */
    public function to_array() {
        return array('id' => $this->id,
            'likesim' => $this->likesim,
            'followsim' => $this->followsim,
            'popularity' => $this->popularity,
            'depth' => $this->depth);
    }

    /**
     * Sets the follow similarity.
     *
     * @param int $uid The user ID.
     * @param the $swid The subwiki ID.
     */
    private function set_follow_sim($uid, $swid) {
        Global $DB;
        $sql = 'SELECT COUNT(usertoid) AS total, COUNT(DISTINCT usertoid) AS different
            FROM {socialwiki_follows}
            WHERE (userfromid=? OR userfromid=?) AND subwikiid=?';
        $data = $DB->get_record_sql($sql, array($this->id, $uid, $swid));
        if ($data->total > 0) {

            // Get the similarity between follows and divide by the number of unique likes.
            $this->followsim = ($data->total - $data->different) / $data->different;
        }
    }

    /**
     * Sets the like similarity.
     *
     * @param int $uid The user ID.
     * @param the $swid The subwiki ID.
     */
    private function set_like_sim($uid, $swid) {
        Global $DB;
        $sql = 'SELECT COUNT(pageid) AS total, COUNT(DISTINCT pageid) AS different
            FROM {socialwiki_likes}
            WHERE (userid=? OR userid=?) AND subwikiid=?';
        $data = $DB->get_record_sql($sql, array($this->id, $uid, $swid));

        // Get the similarity between likes and divide by unique likes.
        if ($data->different != 0) {
            $this->likesim = ($data->total - $data->different) / $data->different;
        }
    }

    /**
     * Gets a peer.
     * KEEP PEERS in SESSION variable!
     *
     * @param int $id A user ID.
     * @param int $swid The subwiki ID.
     * @param int $thisuser The current user ID.
     * @return stdClass peer
     */
    public static function socialwiki_get_peer($id, $swid, $thisuser = null) {
        Global $USER;
        // Get peer lists from session.
        if ($thisuser == null) {
            $thisuser = $USER->id;
        }

        if (!isset($_SESSION['socialwiki_session_peers'])) {
            $_SESSION['socialwiki_session_peers'] = array();
        }

        $sessionpeers = $_SESSION['socialwiki_session_peers'];

        if (!isset($sessionpeers[$id])) {
            $p = self::make_with_indicators($id, $swid, $thisuser);
            $sessionpeers[$id] = $p->to_array();
            $_SESSION['socialwiki_session_peers'] = $sessionpeers;
        }

        return new socialwiki_peer($sessionpeers[$id]);
    }

    /**
     * Recalculate peer indicators.
     *
     * @param bool $updatelikes Recalculate like similarity (after a like has happened).
     * @param bool $updatenetwork Recalculate follow similarity and network distance (after a follow has happened).
     * @param int $swid The subwiki ID.
     * @param int $thisuser This user ID.
     */
    public static function socialwiki_update_peers($updatelikes, $updatenetwork, $swid, $thisuser = null) {
        Global $USER;
        // Get peer lists from session.
        if ($thisuser == null) {
            $thisuser = $USER->id;
        }

        if (!isset($_SESSION['socialwiki_session_peers'])) {
            return;
        }

        $sessionpeers = $_SESSION['socialwiki_session_peers'];
        foreach ($sessionpeers as $peerinfo) {
            $peer = new socialwiki_peer($peerinfo);  // Get peer from session var.
            if ($updatelikes) {
                $peer->set_like_sim($thisuser, $swid);
            }

            if ($updatenetwork) {
                $peer->compute_depth($thisuser, $swid);
                $peer->set_follow_sim($thisuser, $swid);
            }

            $sessionpeers[$peer->id] = $peer->to_array(); // Place back into session.
        }

        $_SESSION['socialwiki_session_peers'] = $sessionpeers;
    }

}