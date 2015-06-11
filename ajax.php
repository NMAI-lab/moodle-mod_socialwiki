<?php

//this file is used for ajax calls from search.js
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
require_once($CFG->dirroot . '/mod/socialwiki/socialwikitree.php');

$action = required_param('action', PARAM_TEXT);
$scale = required_param('scale', PARAM_RAW);
$pages = optional_param('pages', null, PARAM_RAW);
$peers = optional_param('peers', null, PARAM_RAW);
$nodes = optional_param('nodes', null, PARAM_RAW);

switch ($action) {
    case 'tree':
        if (isset($nodes) && isset($peers)) {
            $tree = new socialwiki_tree;
            //decode from JavaScript
            $nodes = json_decode($nodes);
            $peers = json_decode($peers);
            $scale = json_decode($scale);
            //there is probably a better way to do this
            //need to get cm so get it from the first page
            foreach ($nodes as $node) {
                $page = socialwiki_get_page(substr($node->id, 1));
                if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
                    print_error('incorrectsubwikiid', 'socialwiki');
                }
                if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
                    print_error('incorrectwikiid', 'socialwiki');
                }

                if (!$cm = get_coursemodule_from_instance('socialwiki', $wiki->id)) {
                    print_error('invalidcoursemodule');
                }
                $PAGE->set_cm($cm);
                break;
            }

            //re-score nodes
            foreach ($nodes as $node) {
                $page = socialwiki_get_page(substr($node->id, 1));
                $page->trust = 0;
                $page->time = $page->timecreated / time();
                $page->likesim = 0;
                $page->followsim = 0;
                $page->peerpopular = 0;
                $page->votes = $page->time;
                foreach ($peers as $peer) {
                    if (socialwiki_liked($peer->id, $page->id)) {
                        $page->votes+=$peer->score;
                        $page->trust+=$peer->trust * $scale->trust;
                        $page->likesim+=$peer->likesim * $scale->like;
                        $page->followsim+=$peer->followsim * $scale->follow;
                        $page->peerpopular+=$peer->popularity * $scale->popular;
                    }
                }
                $tree->add_node($page);
            }
            $tree->add_children();
            //sort tree 
            $tree->sort();
            echo json_encode($tree->nodes);
        }
        break;
    //re-score pages and return the outputted html table
    case 'pages':
        if (isset($pages) && isset($peers)) {
            //decode from JavaScript
            $pages = json_decode($pages);
            $peers = json_decode($peers);
            $scale = (array) json_decode($scale);
            $pages = socialwiki_order_pages_using_peers($peers, $pages, $scale);
            $table = new html_table();
            $table->attributes['class'] = 'socialwiki_editor generalbox colourtext';
            $table->align = array('center');
            if (count($pages) > 0) {
                foreach ($pages as $page) {
                    $table->data[] = array(html_writer::link($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $page->id, $page->title . ' (ID:' . $page->id . ')', array('class' => 'socialwiki_link')));
                    $table->data[] = array('Total Score: ' . $page->votes . '<br/>Trust Score: ' . $page->trust . '<br/>Follow Similarity Score: ' . $page->followsim . '<br/>Like Similarity Score: ' . $page->likesim . '<br/>Peer Popularity Score: ' . $page->peerpopular . '<br/>Time Score: ' . $page->time);
                }
            } else {
                $table->data[] = array('<h3 socialwiki_titleheader>No Pages Found</h3>');
            }
            echo html_writer::table($table);
        }
        break;
    //return nothing if no action is passed
    default:
        break;
}