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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.


require_once($CFG->dirroot . "/mod/socialwiki/locallib.php");
require_once($CFG->dirroot . "/mod/socialwiki/peer.php");

class socialwiki_node {

    //the page id
    public $id;
    public $swid;
    //page title and authors name
    public $content;
    //boolean true if the node isn't a leaf
    //public $hidden;
    //the column the node is in 
    //public $column;
    //an array of children nodes
    public $children = array();
    //the parents id
    public $parent;
    public $peerlist = array(); //kludge: should be page property, not node property
    public $trustvalue;

    //whether the mode has been added to the tree
    //public $added;
    //the level of the tree the node is on
    //public $level;
    //the rank of a node the higher the priority the higher it appears on the search page
    //public $priority=0;


    function __construct($page) {
        $this->id = 'l' . $page->id;
        $this->swid = $page->subwikiid;
        if ($page->parent == NULL || $page->parent == 0) {
            $this->parent = -1;
        } else {
            $this->parent = 'l' . $page->parent;
        }
        $this->compute_trust($page);
        //$this->column=-1;
        //$this->added=false;
        //$this->hidden = true;
        $this->set_content($page);
        /* if(isset($page->votes)){
          $this->priority=$page->votes;
          } */
    }

    function compute_trust($page) {
        $this->peerlist = socialwiki_get_likers($page->id, $page->subwikiid);

        $this->trustvalue = count($this->peerlist); //set default trust value to popularity
    }

    // requires trust to be already computed (above)!
    function set_content($page) {
        Global $PAGE, $CFG, $OUTPUT;
        $user = socialwiki_get_user_info($page->userid);
        //buttons to minimize and collapse
        $this->content = html_writer::start_tag('img', array('title' => 'Collapse', 'id' => 'cop' . $this->id, 'src' => $OUTPUT->pix_url('t/up'), 'class' => 'collapser', 'value' => $this->id));
        $this->content.= html_writer::end_tag('img');
        $this->content.= html_writer::start_tag('img', array('title' => 'Minimize', 'id' => 'hid' . $this->id, 'src' => $OUTPUT->pix_url('t/less'), 'class' => 'hider', 'value' => $this->id));
        $this->content.= html_writer::end_tag('img');

        $this->content.= html_writer::start_tag('span', array('id' => 'content' . $this->id));
        //title
        $this->content.= html_writer::start_tag('span', array('class' => 'titletext'));
        $this->content.= html_writer::link($CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $page->id, $page->title . ' ID: ' . $page->id, array('style' => 'padding:0px;'));
        $this->content.= html_writer::end_tag('span');
        //user and date
        $userlink = mod_socialwiki_renderer::makeuserlink($user->id, $PAGE->cm->id, $page->subwikiid);
        $this->content.= html_writer::link($userlink->out(false), fullname($user)) . "&nbsp; " . socialwiki_format_time($page->timemodified);
        $this->content.= html_writer::end_tag('span');
        //html_writer::link($userlink->out(false),fullname($user),array("class"=>"colourtext"));
        /* if(isset($page->votes)){
          //add page scores
          $this->content.='<br/>Total Score: '.$page->votes.
          '<br/>Trust Score: '.$page->trust.
          '<br/>Follow Similarity Score: '.$page->followsim.
          '<br/>Like Similarity Score: '.$page->likesim.
          '<br/>Peer Popularity Score: '.$page->peerpopular.
          '<br/>Time Score: '.$page->time;
          } */
    }

    function add_child($child) {
        $this->children[] = $child;
    }

    function to_HTML_List() {
        //Global $OUTPUT;
        $branch = '<li><div class="tagcloud" rel="' . $this->trustvalue . '">' . $this->content . '</div>';
        if (!empty($this->children)) {
            $branch .='<ul id="' . $this->id . '">';
            foreach ($this->children as $child) {
                $branch .= $child->to_HTML_List(); // recursively display children
            }
            $branch .='</ul>';
        }

        $branch .='</li>';   //$OUTPUT->box($this->content,'socialwiki_treebox colourtext');
        return $branch;
    }

    function list_peers_rec() {
        $plist = $this->peerlist; //arrays copied by value (a bit deeper than shallow copy!)
        foreach ($this->children as $child) {
            $plist = array_merge($plist, $child->list_peers_rec());
        }
        return $plist;
    }

}

class socialwiki_tree {

    //an array of socialwiki_nodes

    public $nodes = array();
    public $roots = array(); // all the nodes with no parent.

    //build an array of nodes

    function build_tree($pages) {
        //echo 'building tree with pages =';

        foreach ($pages as $page) {
            //echo $page->id.', ';  //see the order the pages are outputed
            $this->add_node($page);
        }

        $this->add_children();
        //var_dump($this->roots);
    }

    //add a node to the nodes array
    function add_node($page) {
        $this->nodes['l' . $page->id] = new socialwiki_node($page);
    }

    //add the children arrays to nodes
    function add_children() {
        //if the array has a parent add it to the parents child array
        foreach ($this->nodes as $node) {
            if ($node->parent != -1) {
                if (isset($this->nodes[$node->parent])) {
                    $parent = $this->nodes[$node->parent];
                    $parent->add_child($node);
                } else {
                    print_error('nonode', 'socialwiki'); //TODO: what to do if the parent node is absent: 
                    //TODO: include a fictitious node? problem: lineage is broken.
                    //for now: just create another root 
                    $this->roots[] = $node;
                }
            } else { //root node
                $this->roots[] = $node; // add to list of root nodes
            }
        }
    }

    //sorts the nodes so that the family of the leaf with the highest priority is first
    //the order is parents followed by children 
    /* function sort(){
      $leaves=$this->find_leaves();
      $sorted=array();
      //sort leaves in order of priority
      $leaves=socialwiki_merge_sort_nodes($leaves);

      for($i=0;$i<count($leaves);$i++){
      //if the parent is already in the tree add the leaf in the proper position
      if(array_key_exists($leaves[$i]->parent,$sorted)){
      $keyindex=$this->find_index($leaves[$i]->parent,$sorted);
      $copy=$sorted;
      $sorted=array_splice($sorted,0,$keyindex)+array($leaves[$i]->id=>$leaves[$i])+array_splice($copy,$keyindex);
      }else{
      $sorted[$leaves[$i]->id]=$leaves[$i];
      if($leaves[$i]->parent!=-1){
      $sorted=$this->add_parent($leaves[$i]->id,$sorted,1);
      }
      }
      }

      $this->nodes=$sorted;
      foreach($this->nodes as $node){
      if($node->parent==-1){
      $this->add_levels($node->id,1);
      }
      }
      } */

    /* function add_levels($id,$level){
      $this->nodes[$id]->level=$level;
      $level++;
      if(count($this->nodes[$id]->children)>0){
      foreach($this->nodes[$id]->children as $childid){
      $this->add_levels($childid,$level);
      }
      }
      } */


    /* function repos_children($node,&$ar){	
      $removed=array();
      //remove node from array so doesn't affect find_index
      unset($ar[$node->id]);
      //remove children from array so doesn't affect find_index
      foreach($node->children as $childid){
      $removed[]=$childid;
      unset($ar[$childid]);
      }
      //add the node in the proper place
      $keyindex=$this->find_index($node->parent,$ar);
      $copy=$ar;
      $ar=array_splice($ar,0,$keyindex)+array($node->id=>$node)+array_splice($copy,$keyindex);
      //reposition all nodes that where removed along with their children
      for($i=0;$i<count($removed);$i++){
      $this->repos_children($this->nodes[$removed[$i]],$ar);
      }
      } */


    //recursively add parent nodes to an array
    /* function add_parent($childid,$ar){
      //get the child and parent nodes
      $childnode=$this->nodes[$childid];
      $node=$this->nodes[$childnode->parent];

      //add the parent to the array
      if(array_key_exists($childnode->parent,$ar)){
      //if the parent is already there add child beside sibling and remove from the end of the array
      $this->repos_children($childnode,$ar);
      }else{

      //add the parent ahead of the child in the array
      $keyindex=array_search($childid,array_keys($ar));
      $copy=$ar;
      $ar=array_splice($ar,0,$keyindex)+array($node->id=>$node)+array_splice($copy,$keyindex);
      }
      //add parent if it's not already in the array
      if($node->parent!=-1){
      $ar=$this->add_parent($node->id,$ar);
      }
      return $ar;
      } */


    //returns an array with all the leaves of the tree
    /* function find_leaves(){
      $leaves=array();
      foreach($this->nodes as $node){
      if(count($node->children)==0){
      $leaves[]=$node;
      }
      }
      return $leaves;
      } */

    //finds the index a sibling node should be placed in according to priority 
    /* function find_index($parentid,$ar){
      $pos=array(); //tracks the positions of the child nodes
      $parent=$this->nodes[$parentid];
      foreach($parent->children as $id){
      if(array_key_exists($id,$ar)){
      $pos[]=array_search($id,array_keys($ar))+1;
      $pos[]=$this->find_index($id,$ar);
      }
      }
      if(count($pos)>0){
      return max($pos);
      }else{
      return array_search($parentid,array_keys($ar))+1;;
      }
      } */

    function display() {
        Global $USER;
        //$this->sort();
        //echo $OUTPUT->heading('OLDEST--->NEWEST',1,'colourtext');

        $treeul = '<div class="tree" id="doublescroll"><ul>'; // doublescroll = hack to put scrollbars at top and bottom using JS
        $allpeerset = array();
        foreach ($this->roots as $node) {
            $treeul .= $node->to_HTML_List(); //recusively descends tree (trees)
            $allpeerset = array_merge($allpeerset, $node->list_peers_rec());
        }
        $treeul .= '</ul></div>';
        $allpeerset = array_unique($allpeerset); //remove duplicates

        $swid = 0; //just to set variable scope... 0 means nothing.
        if (!empty($this->roots)) { // if it's empty there's no tree and no peers so we're ok
            $swid = $this->roots[0]->swid;
        }
        $peerinfo = '<div id="peerinfo" style="display:none"><ul>';
        foreach ($allpeerset as $p) {
            $peerarray = peer::socialwiki_get_peer($p, $swid, $USER->id)->to_array();
            $peerinfo .= '<li>';
            foreach ($peerarray as $k => $v) {
                $peerinfo .= '<' . $k . '>' . $v . '</' . $k . '>';
            }
            $peerinfo .= '</li>';
        }
        $peerinfo .= '</ul></div>';

        echo $treeul . $peerinfo;
    }

}
