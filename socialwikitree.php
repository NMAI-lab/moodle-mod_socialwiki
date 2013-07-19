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

	class socialwiki_node{
		//the page id
		public $id;
		//page title and authors name
		public $content;
		//boolean true if the node isn't a leaf
		public $hidden;
		//the column the node is in 
		public $column;
		//an array of children ids
		public $children=array();
		//the parents id
		public $parent;
		//whether the mode has been added to the tree
		public $added;
		//the level of the tree the node is on
		public $level;
		//the rank of a node the higher the priority the higher it appears on the search page
		public $priority;
		
		
		function __construct($page){
			$this->id='l'.$page->id;
			if($page->parent==NULL||$page->parent==0){
				$this->parent=-1;
			}else{
				$this->parent='l'.$page->parent;
			}
			$this->column=-1;
			$this->added=false;
			$this->hidden = true;
			$this->set_content($page);
			$this->set_priority($page);
		}
	
	private function set_content($page){
		Global $PAGE,$CFG;
		$user = socialwiki_get_user_info($page->userid);
		$userlink = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $PAGE->cm->course));
		$this->content=html_writer::link($CFG->wwwroot.'/mod/socialwiki/view.php?pageid='.$page->id,$page->title,array("class"=>"colourtext")).'<br/>'.html_writer::link($userlink->out(false),fullname($user),array("class"=>"colourtext"));
	}
	
	//sets priority to number of likes
	function set_priority($page){
		$this->priority=socialwiki_numlikes($page->id);
	}
	
	function add_child($child){
		$this->children[]=$child;
	}
	
	
	function display(){
		Global $OUTPUT;
		echo $OUTPUT->box($this->content,'socialwiki_treebox');
	}
}


class socialwiki_tree{
	//an array of socialwiki_nodes
	public $nodes=array();
	
	//build an array of nodes
	function build_tree($pages){
		foreach ($pages as $page){
			$this->add_node($page);
		}
		$this->add_children();
	
	}
	
	//add a node to the nodes array
	function add_node($page){
		$this->nodes['l'.$page->id]=new socialwiki_node($page);
	}
	
	//add the children arrays to nodes
	function add_children(){
					//if the array has a parent add it to the parents child array
		foreach ($this->nodes as $node){
			if($node->parent!=-1){
				$parent=$this->find_node($node->parent);
				if($parent){
					$parent->add_child($node->id);
				}else{
					print_error('nonode','socialwiki');
				}
			}
		}
	}
	
	//finds a node given an id returns that node if found. -1 if the node doesn't exist
	function find_node($nodeid){
		foreach($this->nodes as $node){
			if($node->id==$nodeid){
				return $node;
			}
		}
		return NULL;
	}
	
	function sort(){
		$leaves=$this->find_leaves();
		$sorted=array();
		//sort leaves in order of priority
		for($i=1;$i<count($leaves);$i++){
			for($j=0;$j<count($leaves)&&$j<$i;$j++){
				if($leaves[$i]->priority>$leaves[$j]->priority){
					$temp=$leaves[$i];
					$leaves[$i]=$leaves[$j];
					$leaves[$j]=$temp;
				}
			}
		}
		
		for($i=0;$i<count($leaves);$i++){
			//if the parent is already in the tree add the leaf in the proper position
			if(array_key_exists($leaves[$i]->parent,$sorted)){
				$keyindex=$this->find_index($leaves[$i]->parent,$sorted);
				//print_object($leaves[$i]);
				//print_object($keyindex);
				//print_object(array_keys($sorted));
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
	}
	
	function add_levels($id,$level){
		$this->nodes[$id]->level=$level;
		$level++;
		if(count($this->nodes[$id]->children)>0){
			foreach($this->nodes[$id]->children as $childid){
				$this->add_levels($childid,$level);
			}
		}
	}
	

	function repos_children($node,&$ar){	
			$removed=array();
			unset($ar[$node->id]);
			foreach($node->children as $childid){
				$removed[]=$childid;
				unset($ar[$childid]);
			}
		$keyindex=$this->find_index($node->parent,$ar);
		if($node->id=='l329'){
				print_object($ar);
				print_object($keyindex);
			}
		$copy=$ar;
		$ar=array_splice($ar,0,$keyindex)+array($node->id=>$node)+array_splice($copy,$keyindex);
		if(count($node->children)>0){
		}
		for($i=0;$i<count($removed);$i++){
			$this->repos_children($this->nodes[$removed[$i]],$ar);
		}
	}
	

	//recursively add parent nodes to an array
	function add_parent($childid,$ar){
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
	}
	
	
	//returns an array with all the leaves of the tree
	function find_leaves(){
		$leaves=array();
		foreach($this->nodes as $node){
			if(count($node->children)==0){
			$leaves[]=$node;
			}
		}
		return $leaves;
	}
	
	//finds the index a sibling node should be placed in according to priority 
	function find_index($parentid,$ar){
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
	}
	
	function display(){
		Global $OUTPUT;
		$this->sort();
		echo $OUTPUT->heading('OLDEST--->NEWEST',1,'colourtext');
		foreach($this->nodes as $node){
			if($node->parent==-1){
				echo'<br/><br/><br/>';
			}
			echo $OUTPUT->container_start();
			echo str_repeat('&nbsp',($node->level-1)*8);
			$node->display();
			echo $OUTPUT->container_end();
		}
	}

}
