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

Global $CFG, $PAGE;

require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/peer.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/table.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/userTable.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/topicsTable.php');
require_once($CFG->dirroot . '/mod/socialwiki/table/versionTable.php');
//allowed tabletypes
const FAVES= 'faves';
const RECENTLIKES = 'recentlikes';
const NEWPAGEVERSIONS= 'newpageversions';
const ALLPAGEVERSIONS= 'allpageversions'; 
const FOLLOWEDUSERS= 'followedusers'; 
const FOLLOWERS= 'followers'; 
const ALLUSERS= 'allusers'; 
const ALLTOPICS= 'alltopics'; 
const USERFAVES= 'userfaves';
const VERSIONSFOLLOWED = 'versionsfollowed';



$tabletype = required_param('type', PARAM_TEXT);
$userid = required_param('userid', PARAM_INT);
$swid = required_param('swid', PARAM_INT);
$courseid = optional_param('courseid',0, PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$targetuser = optional_param('targetuser', 0, PARAM_INT); 
$trustcombiner = optional_param('trustcombiner', '', PARAM_TEXT); //max, min, sum, avg
//when we view another user's page

//not sure I fully understand this...
$cm = get_coursemodule_from_id('socialwiki', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course, true, $cm);

//echo '<p>';
//var_dump($_SESSION);
//echo '</p>';

$PAGE->set_cm($cm);


$t = null;
switch($tabletype){
	case "faves":
		$t= versionTable::makeFavouritesTable($userid, $swid );
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
	case "recentlikes":
		$t= versionTable::makeRecentLikesTable($userid, $swid);
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
	case "versionsfollowed":
		$t= versionTable::makeContentFromFollowedTable($userid, $swid);
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
	case "newpageversions":
		$t= versionTable::makeNewPageVersionsTable($userid, $swid);
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
	case "allpageversions":
		$t= versionTable::makeAllVersionsTable($userid, $swid);
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
        case "mypageversions":
		$t= versionTable::makeUserVersionsTable($userid, $swid);
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
        case "userpageversions":
		$t= versionTable::makeUserVersionsTable($targetuser, $swid);
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
	case "userfaves": //faves by another user
		$t = versionTable::make_A_User_Faves_table($userid, $swid, $targetuser);
		if ($trustcombiner!='' and $t!= null)
			$t->set_trust_combiner($trustcombiner);
		break;
	case "followedusers":
		$t = UserTable::make_followed_users_table($userid, $swid);
		break;
	case "followers":
		$t = UserTable::make_followers_table($userid, $swid);
		break;
	case "allusers":
		$t = UserTable::make_all_users_table($userid, $swid);
		break;
	case "alltopics":
		$t = TopicsTable::make_all_topics_table($userid, $swid, $courseid, $cmid);
		break;
	default:
		$tabletype ='unknowntabletype';
}
if ($t!=null)
	echo $t->get_as_HTML();
else {
	$message= get_string('no'.$tabletype, 'socialwiki');

	echo "<table><tr><td>$message</td></tr></table>";
}

	

