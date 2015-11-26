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
 * The version table for showing the page versions.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Version Table Class.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class socialwiki_versiontable extends socialwiki_table {

    /**
     * Maps peerid to peer object for all peers.
     *
     * @var array
     */
    private $allpeers;

    /**
     * Maps pageid to page object, with additional field $p->likers containing array of likers (peerids).
     *
     * @var array
     */
    private $allpages;

    /**
     * Way of combining user trust indicators.
     *
     * @var string
     */
    private $combiner;

    /**
     * Create a version table.
     *
     * @param int $uid The current uid (userid).
     * @param int $swid The current subwikiid.
     * @param list $pages What pages are in the table.
     * @param string $type Table header options.
     * @param string $combiner How to combine peer data.
     */
    public function __construct($uid, $swid, $pages, $type, $combiner = 'avg') {
        parent::__construct($uid, $swid, $type, ' typev');
        $this->get_all_likers($pages); // Get all peers involved, store info in $this->allpages and this->allpeers.
        $this->combiner = $combiner;
    }

    /**
     * Build the table data structure.
     *
     * @return array $table Each row being an array of head=>value pairs
     */
    protected function get_table_data() {
        Global $CFG;

        $table = array();

        foreach ($this->allpages as $page) {
            $views = $page->pageviews;
            $likes = socialwiki_numlikes($page->id);

            // Get all contributors.
            $contributors = socialwiki_get_contributors($page->id);
            $contribstring = $this->make_multi_user_div($contributors);

            $linkpage = "<a style='margin:0;' class='socialwiki-link' href="
                    . "$CFG->wwwroot/mod/socialwiki/view.php?pageid=$page->id>$page->title</a>";

            if (socialwiki_liked($this->uid, $page->id)) {
                $likeimg = "<img style='width:22px' class='socialwiki_unlikeimg unlikeimg_$page->id' "
                        . "alt='unlikeimg_$page->id' src='$CFG->wwwroot/mod/socialwiki/pix/icons/unlike.png'>";
            } else {
                $likeimg = "<img style='width:22px' class='socialwiki_likeimg likeimg_$page->id'  "
                        . "alt='likeimg_$page->id' src='$CFG->wwwroot/mod/socialwiki/pix/icons/like.png'>";
            }

            // Favourites.
            $favourites = socialwiki_get_page_favourites($page->id, $this->swid);
            $favdiv = $this->make_multi_user_div($favourites);

            $combiner = $this->combiner;

            // Trust indicators.
            $peerpop = $this->combine_indicators($page, $combiner, "peerpopularity");
            $likesim = $this->combine_indicators($page, $combiner, "likesimilarity");
            $followsim = $this->combine_indicators($page, $combiner, "followsimilarity");
            $distance = $this->combine_indicators($page, $combiner, "networkdistance");

            $row = array(
                'title' => $likeimg.$linkpage,
                'contributors' => $contribstring,
                'updated' => "<span value='{$page->timecreated}'>" . socialwiki_format_time($page->timecreated) . "</span>",
                'likes' => $likes,
                'views' => $views,
                'favourite' => $favdiv,
                'popularity' => round($peerpop, 2),
                'likesim' => round($likesim * 100) . '%',
                'followsim' => round($followsim * 100) . '%',
                'networkdistance' => substr($distance, 0, 4)
            );
            // Add trust values.
            $table[] = array_intersect_key($row, array_flip($this->headers)); // Filter to get only the requested headers.
        }
        return $table;
    }

    /**
     * Make a div containing the contributors.
     *
     * @param array $contributors The relevant contributors.
     * @return string HTML
     */
    private function make_multi_user_div($contributors) {
        Global $CFG, $PAGE;
        $idfirst = array_pop($contributors);
        $firstctr = fullname(socialwiki_get_user_info($idfirst));
        $num = count($contributors);
        if ($num == 1) {
            $firstctr .= " and 1 other";
        } else if ($num > 1) {
            $firstctr .= " and $num others";
        }

        $ctr = "";
        if ($num != 0) {
            $ctr = "Others:\n";
            foreach (array_reverse($contributors) as $c) {
                $ctr .= fullname(socialwiki_get_user_info($c)) . "\n";
            }
        }

        if ($idfirst == $this->uid) {
            $href = "href='$CFG->wwwroot/mod/socialwiki/home.php?id={$PAGE->cm->id}'";
        } else {
            $href = "href='$CFG->wwwroot/mod/socialwiki/viewuserpages.php?userid=$idfirst&subwikiid=$this->swid'";
        }

        return "<a class='socialwiki-link' $href title='$ctr'>$firstctr</a>";
    }

    /**
     * Combines trust indicators obtained from the peers who like a page.
     *
     * @param array $page Group of all the pages.
     * @param string $reducer How to reduce the peers.
     * @param string $indicator What should be combined.
     * @return int
     */
    private function combine_indicators($page, $reducer, $indicator) {
        $uservals = array();
        foreach ($page->likers as $u) {
            $peer = $this->allpeers[$u];

            $score = 0; // Meant to stand out if errors come up.
            switch ($indicator) {
                case "followsimilarity":
                    $score = $peer->followsim;
                    break;
                case "likesimilarity":
                    $score = $peer->likesim;
                    break;
                case "peerpopularity":
                    $score = $peer->popularity;
                    break;
                case "networkdistance":
                    $score = max(0, $peer->depth);
                    break;
            }
            $uservals[] = $score;
        }
        if (count($uservals) == 0) {
            return 0;
        }

        switch ($reducer) {
            case "max":
                return max($uservals);
            case "min":
                return min($uservals);
            case "avg":
                $len = count($uservals);
                return (array_reduce($uservals, function($a, $b) {
                            return $a + $b;
                }) / $len);
            case "sum":
                return array_reduce($uservals, function($a, $b) {
                    return $a + $b;
                });
        }

        return 0.99; // Kludge: just an error value.
    }

    /**
     * Get list of users that like any of the pages, with all their relevant info
     * add the pages to $this->allpages and the peers to the existing list of peers
     *
     * @param array $pagelist The list of pages.
     */
    private function get_all_likers($pagelist) {
        $peerids = array();
        foreach ($pagelist as $p) {
            $likers = socialwiki_get_page_likes($p->id, $this->swid); // Gets list of user likers.
            $p->likers = $likers;
            $this->allpages[$p->id] = $p; // Add pages to list.
            $peerids = array_unique(array_merge($peerids, $likers));
        }

        $this->allpeers = $this->get_peers($peerids); // See below.
        // TODO: need to merge into existing list instead of overwriting.
    }

    /**
     * Get peers from user IDs, with all relevant info: used by get_all_likers.
     *
     * @param array $ids The ID's of the peers.
     * @return array
     */
    private function get_peers($ids) {
        $me = $this->uid;
        $swid = $this->swid;

        // Define function to get peer from userid.
        $buildfunction = function ($id) use ($me, $swid) {
            return socialwiki_peer::socialwiki_get_peer($id, $swid, $me);
        };
        return array_combine($ids, array_map($buildfunction, $ids));
        // Will return an associative array with peerid => peer object for each peerid.
    }

    // 0======================================================================.
    // Factory Method
    // 0======================================================================.

    /**
     * Generate a favorited versions table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @param string $combiner How to combine the peers.
     * @return \socialwiki_versiontable
     */
    public static function favourites_versiontable($uid, $swid, $combiner = 'avg') {
        if ($favs = socialwiki_get_user_favourites($uid, $swid)) {
            return new socialwiki_versiontable($uid, $swid, $favs, 'mystuff', $combiner);
        }
        return null;
    }

    /**
     * Generate a liked versions table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @param string $combiner How to combine the peers.
     * @return \socialwiki_versiontable
     */
    public static function likes_versiontable($uid, $swid, $combiner = 'avg') {
        $ids = socialwiki_get_user_likes($uid, $swid);
        $likes = array();
        foreach ($ids as $id) {
            array_push($likes, socialwiki_get_page($id->pageid));
        }

        if (!empty($likes)) {
            return new socialwiki_versiontable($uid, $swid, $likes, 'mystuff', $combiner);
        }
        return null;
    }

    /**
     * Generate a followed versions table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @param string $combiner How to combine the peers.
     * @return \socialwiki_versiontable
     */
    public static function followed_versiontable($uid, $swid, $combiner = 'avg') {
        $pages = socialwiki_get_pages_from_followed($uid, $swid);

        if ($pages) {
            return new socialwiki_versiontable($uid, $swid, $pages, 'version', $combiner);
        }
        return null;
    }

    /**
     * Generate a new versions table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @param string $combiner How to combine the peers.
     * @return \socialwiki_versiontable
     */
    public static function new_versiontable($uid, $swid, $combiner = 'avg') {
        $pages = socialwiki_get_updated_pages_by_subwiki($swid, $uid);

        if ($pages) {
            return new socialwiki_versiontable($uid, $swid, $pages, 'version', $combiner);
        }
        return null;
    }

    /**
     * Generate an all versions table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @param string $combiner How to combine the peers.
     * @return \socialwiki_versiontable
     */
    public static function all_versiontable($uid, $swid, $combiner = 'avg') {
        $pages = socialwiki_get_page_list($swid);

        if (!empty($pages)) {
            return new socialwiki_versiontable($uid, $swid, $pages, 'version', $combiner);
        }
        return null;
    }

    /**
     * Generate a user's versions table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @param string $combiner How to combine the peers.
     * @return \socialwiki_versiontable
     */
    public static function user_versiontable($uid, $swid, $combiner = 'avg') {
        $pages = socialwiki_get_user_page_list($uid, $swid);

        if (!empty($pages)) {
            return new socialwiki_versiontable($uid, $swid, $pages, 'mystuff', $combiner);
        }
        return null;
    }

    /**
     * Generate a general versions table.
     *
     * @param int $uid The user ID.
     * @param int $swid The subwiki ID.
     * @param array $pages All the pages to show in the table.
     * @param string $type The type of table decides the headers.
     * @return \socialwiki_versiontable
     */
    public static function html_versiontable($uid, $swid, $pages, $type) {
        if (!empty($pages)) {
            return new socialwiki_versiontable($uid, $swid, $pages, $type); // Defined in parent class.
        }
        return null;
    }
}