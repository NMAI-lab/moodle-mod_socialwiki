<?php
	require_once('../../config.php');
	require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
	$pageid=required_param('pageid', PARAM_INT);
	$from=required_param('from',PARAM_TEXT);
	
	if (!$page = socialwiki_get_page($pageid)) {
    print_error('incorrectpageid', 'socialwiki');
}

if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
    print_error('incorrectsubwikiid', 'socialwiki');
}

if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
    print_error('incorrectwikiid', 'socialwiki');
}

if (!$cm = get_coursemodule_from_instance('socialwiki', $wiki->id)) {
    print_error('invalidcoursemodule');
}
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

	//get the author of the current page
	$page=socialwiki_get_wiki_page_version($pageid,0);
	$user2=$page->userid;
	//make sure the user isn't following themselves
	if($USER->id==$user2){
	//display error with a redirect back to the page hey came from
	$PAGE->set_context($context);
	$PAGE->set_cm($cm);
	$PAGE->set_url('/mod/socialwiki/follow.php');
	echo $OUTPUT->header();
	echo $OUTPUT->box_start('generalbox','socialwiki_followerror');
		echo get_string("cannotfollow", 'socialwiki').'<br/>';
		echo html_writer::link($from,'Go back');
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
	}else{
		//check if the use is already following the author
		if(socialwiki_is_following($USER->id,$user2)){
			//delete the record if the user is already following the author
			socialwiki_unfollow($USER->id,$user2);
		}else{
			//if the user isn't following the author add a new follow
			$record=new StdClass();
			$record->userfromid=$USER->id;
			$record->usertoid=$user2;
			$DB->insert_record('socialwiki_follows',$record);
		}
		
		redirect($from);
	}
