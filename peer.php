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
 * @package socialwiki
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;

require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
require_once($CFG->dirroot . '/mod/socialwiki/peer.php');

//class that describes the similarity between the current user and another student in the activity
class peer {

    public $trust = 0; //trust indicator value = 1/distance or 0
    public $id; //the user id
    public $likesim = 0; //the similarity between likes of the peer and user
    public $followsim = 0; //the similarity between the people the user and peer are following
    public $popularity; //percent popularity
    public $depth; //social distance: 1 for I'm following this user, 2 for friend of a friend, etc.

    function __construct($arr) {

        $this->id = $arr['id'];
        $this->likesim = $arr['likesim'];
        $this->followsim = $arr['followsim'];
        $this->popularity = $arr['popularity'];
        $this->depth = $arr['depth'];
    }

    /**
     * creates a new peer and computes its trust indicators
     */
    public static function make_with_indicators($id, $swid, $currentuser, $scale = null) {
        //Global $USER;
        $newpeer = new peer(array('id' => $id, 'likesim' => 0, 'followsim' => 0, 'popularity' => 0, 'depth' => 0));

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
        $newpeer->popularity = socialwiki_get_followers($id, $swid); //not dividing

        return $newpeer;
        /* if($scale == null) {
          $scale = array(
          'trust' => 1,
          'like' => 1,
          'follow' => 1,
          'popular' => 1
          );
          }
          $newpeer->set_score($scale); */
    }

    function compute_depth($userid, $swid) {
        $this->depth = socialwiki_follow_depth($userid, $this->id, $swid);
    }

    function to_array() {
        return array('id' => $this->id,
            'likesim' => $this->likesim,
            'followsim' => $this->followsim,
            'popularity' => $this->popularity,
            'depth' => $this->depth);
    }

    function is_me() {
        Global $USER;
        return ($USER->id == $this->id);
    }

    /*
     * sets the follow similarity to the 
     * @userid the current users id
     * @swid the subwikiid
     */

    function set_follow_sim($userid, $swid) {
        Global $DB;
        $sql = 'SELECT COUNT(usertoid) AS total, COUNT(DISTINCT usertoid) AS different
		FROM {socialwiki_follows} 
		WHERE (userfromid=? OR userfromid=?) AND subwikiid=?';
        $data = $DB->get_record_sql($sql, array($this->id, $userid, $swid));
        if ($data->total > 0) {

            //get the similarity between follows and divide by the number of unique likes  
            $this->followsim = ($data->total - $data->different) / $data->different;
        }
    }

    function set_like_sim($userid, $swid) {
        Global $DB;
        $sql = 'SELECT COUNT(pageid) AS total, COUNT(DISTINCT pageid) AS different
		FROM {socialwiki_likes} 
		WHERE (userid=? OR userid=?) AND subwikiid=?';
        $data = $DB->get_record_sql($sql, array($this->id, $userid, $swid));

        //get the similarity between likes and divide by unique likes 
        if ($data->different != 0) {
            $this->likesim = ($data->total - $data->different) / $data->different;
        }
    }

    /* /sets peer's score to sum of scores times there weight
      function set_score($scale){
      $this->score=$this->trust*$scale['trust']+$this->likesim*$scale['like']+$this->followsim*$scale['follow']+$this->popularity*$scale['popular'];
      } */

/////////////////////////////////////
/// KEEP PEERS in SESSION variable!/
///////////////////////////////////


    static function socialwiki_get_peer($id, $swid, $thisuser = null) {
        Global $USER;
        //get peer lists from session
        if ($thisuser == null) {
            $thisuser = $USER->id;
        }

        if (!isset($_SESSION['socialwiki_session_peers'])) {
            $_SESSION['socialwiki_session_peers'] = array();
            //	echo '<p>no peers in session var!</p>';
        }

        $sessionpeers = $_SESSION['socialwiki_session_peers'];

        if (!isset($sessionpeers[$id])) {
            //	echo '<p>peer '. $id.' not in session var!</p>';
            $p = peer::make_with_indicators($id, $swid, $thisuser);
            $sessionpeers[$id] = $p->to_array();
            $_SESSION['socialwiki_session_peers'] = $sessionpeers;
        }



        return new peer($sessionpeers[$id]);
        /*
          echo '<p>';
          var_dump($_SESSION);
          echo '</p>';
         */
    }

    /**
     * recalculate peer indicators.
     * @param updatelikes: boolean: recalculate like similarity (after a like has happened)
     * @param updatenetwork: boolean: recalculate follow similarity and network distance (after a follow has happened)
     */
    static function socialwiki_update_peers($updatelikes, $updatenetwork, $swid, $thisuser = null) {
        Global $USER;
        //get peer lists from session


        if ($thisuser == null) {
            $thisuser = $USER->id;
        }
        //echo 'hi2<br/>';

        if (!isset($_SESSION['socialwiki_session_peers'])) {
            echo '<p>no peers in session var!</p>';
            return;
        }


        $sessionpeers = $_SESSION['socialwiki_session_peers'];
        //echo 'hi3<br/>';

        foreach ($sessionpeers as $peerinfo) {
            //echo 'peerinfo=';
            //var_dump($peerinfo);

            $peer = new peer($peerinfo);  //get peer from session var

            if ($updatelikes) {

                $peer->set_like_sim($thisuser, $swid);
                //	echo 'hi again2<br/>';
            }


            if ($updatenetwork) {
                $peer->compute_depth($thisuser, $swid);
                $peer->set_follow_sim($thisuser, $swid);
            }

            $sessionpeers[$peer->id] = $peer->to_array(); //place back into session
            //echo 'hi again 3<br/>';
            //die();
        }

        $_SESSION['socialwiki_session_peers'] = $sessionpeers;
    }

}
