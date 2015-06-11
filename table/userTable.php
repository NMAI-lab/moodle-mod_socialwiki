<?php

global $CFG;

require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
require_once($CFG->dirroot . '/mod/socialwiki/peer.php');
//require_once($CFG->dirroot . '/mod/socialwiki/sortableTable/sortableTable.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/table.php');

class UserTable extends socialwiki_table {

    private $columns;
    //private $uid;
    //private $swid;

    private $col_names = array(
        "Name", //TODO: make these all "getString()"
        "Social Distance",
        "Number of Followers",
        "Like Similarity",
        "Follow Similarity",
    );

    public function __construct($uid, $swid, $ids, $headers) {
        parent::__construct($uid, $swid, $headers);
        $this->userlist = $ids;

        $this->headers = array_map(function($h) {
            return get_string($h, 'socialwiki');
        }, $headers);
//		$this->uid = $uid;
//		$this->swid = $swid;
    }

    /*     * *
     * Template to create a table using a select/project (select A where B):
     * 1: use a specific DB access function to retrieve a subset of the users, 
     *   it may be a superset of what we ultimately want. Examples:
     * 	- get all users
     * 	- get followers of some user
     * 	
     * 2: refine select condition by applying a filter
     * 3 project by passing a subset of the headers with the make_table method. Examples:
      (all headers)
      $h = array("name", "distance","popularity", "likesim", "followsim");
      (just name and popularity)
      $h = array("name", popularity");

     */

    /**
     * returns all users except 'me'
     */
    public static function make_all_users_table($me, $swid) {
        $ids = socialwiki_get_active_subwiki_users($swid);
        //$me = $this->uid;

        $ids = array_filter($ids, function($i) use ($me) {
            return ($i != $me);
        });
        $h = array("name", "networkdistance", "popularity", "likesim", "followsim");
        return new UserTable($me, $swid, $ids, $h);
        //return $this->make_table($ids, "all_user_table", $h);
    }

    /**
     * returns a UserTable with all users I follow
     */
    public static function make_followed_users_table($uid, $swid) {
        $ids = socialwiki_get_follows($uid, $swid);
        // this returns an array of arrays where the keys are the user ids, each one associated with a pair 
        // "usertoid"=>"uid"
        $ids = array_keys($ids);
        if (empty($ids)) {
            return null; //'<h3>'.get_String('youfollownobody', 'socialwiki').'</h3>';
        }
        $h = array("name", "popularity", "likesim", "followsim");
        return new UserTable($uid, $swid, $ids, $h);
        //return $this->make_table($ids, 'followed_user_table', $h);
    }

    /**
     * returns a UserTable with all my followers
     */
    public static function make_followers_table($uid, $swid) {
        $ids = socialwiki_get_follower_users($uid, $swid);
        //var_dump($ids);
        if (empty($ids)) {
            return null;
            //return '<h3>'.get_String('youhavenofollowers', 'socialwiki').'</h3>';
        }

        $h = array("name", "popularity", "likesim", "followsim");
        //return $this->make_table($ids, 'followers_user_table', $h);
        return new UserTable($uid, $swid, $ids, $h);
    }

    /* to be done 
      public function get_table_data(){
      return array(array('NOT IMPLEMENTED'));
      } */

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
        //$number_of_users = socialwiki_get_user_count($this->swid); //total number of users is used with followers data
        $me = $this->uid;
        $swid = $this->swid;
        $www = $CFG->wwwroot;

        //define function to build a row from a user
        $build_function = function ($id) use ($headers, $me, $swid, $www) { //include headers variable as it indicates which headers are needed
            //echo "error here:";
            //var_dump($id);
            $user = socialwiki_get_user_info($id);
            $name = "<a style='margin:0;' class='socialwiki_link' href='" . $www . "/mod/socialwiki/viewuserpages.php?userid=" . $user->id . "&subwikiid=" . $swid . "'>" . fullname($user) . "</a>";

            //echo 'New Peer: '.$id;
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

        $tabledata = array_map($build_function, $ids); //end array_map
        //echo 'the array is built';

        return $tabledata;
    }

    /*
      //make Sortable Table from a list of ids, with given column headers
      private function make_table($ids, $table_id, $headers) {
      //get data for the table
      //var_dump($ids);
      $rows = $this->get_table_data($ids, $headers);

      //make a sortable table out of that
      $table = SortableTable::buildFromData($rows);

      $table_markup = "";

      $table_markup .= "<div class='yui3-js-endable'>";
      $table_markup .= $table->get_table($table_id);
      $table_markup .= "<div id='$table_id' class='table_region'></div>";
      $table_markup .= "</div>";

      //echo "table markup completed!";

      return $table_markup;
      }
     */
}
