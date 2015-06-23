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

Global $CFG;

$trustcombiner = 'max';  //default for now, should remove entirely
require_once($CFG->dirroot . '/mod/socialwiki/table/userTable.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/topicsTable.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/versionTable.php');

$t = null;
switch ($tabletype) {
    case "recentlikes":      //user likes
        $t = versionTable::makeRecentLikesTable($userid, $swid, $trustcombiner);
        break;
    case "faves":            //user favourites
    case "userfaves":        //favourites by another user
        $t = versionTable::makeFavouritesTable($userid, $swid, $trustcombiner);
        break;
    case "mypageversions":   //user pages
    case "userpageversions": //pages by another user
        $t = versionTable::makeUserVersionsTable($userid, $swid, $trustcombiner);
        break;
    case "versionsfollowed": //versions by followed users
        $t = versionTable::makeFollowedVersionsTable($userid, $swid, $trustcombiner);
        break;
    case "newpageversions":  //new versions
        $t = versionTable::makeNewVersionsTable($userid, $swid, $trustcombiner);
        break;
    case "allpageversions":  //all versions
        $t = versionTable::makeAllVersionsTable($userid, $swid, $trustcombiner);
        break;
    case "followedusers":    //followed users
        $t = userTable::makeFollowedUsersTable($userid, $swid);
        break;
    case "followers":        //followers
        $t = userTable::makeFollowersTable($userid, $swid);
        break;
    case "allusers":         //all users
        $t = userTable::makeAllUsersTable($userid, $swid);
        break;
    case "alltopics":        //all pages (grouped versions)
        $t = topicsTable::makeTopicsTable($userid, $swid);
        break;
    default:
        $tabletype = 'unknowntabletype ' . $tabletype;
}

if ($t != null) {
    return $t->get_as_HTML();
} else {
    $message = get_string('no' . $tabletype, 'socialwiki');
    return "<table><tr><td>$message</td></tr></table>";
}
