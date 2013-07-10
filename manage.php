<?php
	require_once('../../config.php');
	require_once($CFG->dirroot . '/mod/socialwiki/pagelib.php');
	require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
	$pageid=required_param('pageid',PARAM_INT);

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
	$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	
	require_login($course, true, $cm);
	
	$managepage=new page_socialwiki_manage($wiki,$subwiki,$cm);
	$managepage->set_page($page);
	
	$managepage->print_header();
	
	$managepage->print_content();
	
	$managepage->print_footer();
