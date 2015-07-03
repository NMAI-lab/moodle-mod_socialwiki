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

class socialwiki_node {

    // The page ID.
    public $id;
    public $swid;
    // Page title and authors name.
    public $content;
    // An array of children nodes.
    public $children = array();
    // The parents ID.
    public $parent;
    public $peerlist = array(); // Kludge: should be page property, not node property.
    public $trustvalue;

    public function __construct($page) {
        $this->id = 'l' . $page->id;
        $this->swid = $page->subwikiid;
        if ($page->parent == null || $page->parent == 0) {
            $this->parent = -1;
        } else {
            $this->parent = 'l' . $page->parent;
        }
        $this->compute_trust($page);
        $this->set_content($page);
    }

    private function compute_trust($page) {
        $this->peerlist = socialwiki_get_page_likes($page->id, $page->subwikiid);
        $this->trustvalue = count($this->peerlist); // Set default trust value to popularity.
    }

    // Requires trust to be already computed (above)!
    private function set_content($page) {
        Global $PAGE, $CFG, $OUTPUT;
        $user = socialwiki_get_user_info($page->userid);
        // Buttons to minimize and collapse.
        $this->content = html_writer::start_tag('span', array('id' => 'bgroup' . $this->id, 'class' => 'btngroup'));
            $this->content .= html_writer::start_tag('img', array('title' => 'Minimize', 'id' => 'hid' . $this->id,
                'src' => $OUTPUT->pix_url('t/less'), 'class' => 'hider', 'value' => $this->id));
            $this->content .= html_writer::end_tag('img');
            $this->content .= html_writer::start_tag('img', array('title' => 'Collapse', 'id' => 'cop' . $this->id,
                'src' => $OUTPUT->pix_url('t/up'), 'class' => 'collapser', 'value' => $this->id));
            $this->content .= html_writer::end_tag('img');
        $this->content .= html_writer::end_tag('span');

        // Title, user and date.
        $this->content .= html_writer::start_tag('span', array('id' => 'content' . $this->id));
            $this->content .= html_writer::start_tag('span', array('class' => 'titletext'));
            $this->content .= html_writer::link($CFG->wwwroot . '/mod/socialwiki/view.php?pageid='
                    . $page->id, $page->title . ' ID: ' . $page->id, array('style' => 'padding:0px;'));
            $this->content .= html_writer::end_tag('span');
            $userlink = mod_socialwiki_renderer::makeuserlink($user->id, $PAGE->cm->id, $page->subwikiid);
            $this->content .= html_writer::link($userlink->out(false), fullname($user))
                    . "&nbsp; " . socialwiki_format_time($page->timemodified);
        $this->content .= html_writer::end_tag('span');
    }

    public function add_child($child) {
        $this->children[] = $child;
    }

    public function to_html_list() {
        $branch = '<li><div class="tagcloud" rel="' . $this->trustvalue . '">' . $this->content . '</div>';
        if (!empty($this->children)) {
            $branch .= '<ul id="' . $this->id . '">';
            foreach ($this->children as $child) {
                $branch .= $child->to_html_list(); // Recursively display children.
            }
            $branch .= '</ul>';
        }

        $branch .= '</li>';
        return $branch;
    }

    public function list_peers_rec() {
        $plist = $this->peerlist; // Arrays copied by value (a bit deeper than shallow copy!).
        foreach ($this->children as $child) {
            $plist = array_merge($plist, $child->list_peers_rec());
        }
        return $plist;
    }

}

class socialwiki_tree {
    public $nodes = array(); // An array of socialwiki_nodes.
    public $roots = array(); // All the nodes with no parent.

    // Build an array of nodes.
    public function build_tree($pages) {
        foreach ($pages as $page) {
            $this->add_node($page);
        }
        $this->add_children();
    }

    // Add a node to the nodes array.
    public function add_node($page) {
        $this->nodes['l' . $page->id] = new socialwiki_node($page);
    }

    // Add the children arrays to nodes.
    public function add_children() {
        // If the array has a parent add it to the parents child array.
        foreach ($this->nodes as $node) {
            if ($node->parent != -1) {
                if (isset($this->nodes[$node->parent])) {
                    $parent = $this->nodes[$node->parent];
                    $parent->add_child($node);
                } else {
                    print_error('nonode', 'socialwiki'); // TODO: what to do if the parent node is absent.
                    // TODO: include a fictitious node? problem: lineage is broken.
                    // for now: just create another root.
                    $this->roots[] = $node;
                }
            } else { // Root node.
                $this->roots[] = $node; // Add to list of root nodes.
            }
        }
    }

    public function display() {
        Global $USER;
        $treeul = '<div class="tree" id="doublescroll"><ul>'; // Doublescroll puts scrollbar on top and bottom.
        $allpeerset = array();
        foreach ($this->roots as $node) {
            $treeul .= $node->to_html_list(); // Recusively descends tree.
            $allpeerset = array_merge($allpeerset, $node->list_peers_rec());
        }
        $treeul .= '</ul></div>';
        $allpeerset = array_unique($allpeerset); // Remove duplicates.

        $swid = 0; // Just to set variable scope... 0 means nothing.
        if (!empty($this->roots)) { // If it's empty there's no tree and no peers so we're ok.
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