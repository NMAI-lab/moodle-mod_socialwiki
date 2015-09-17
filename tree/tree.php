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
 * The SocialWiki Tree.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * SocialWiki Node Class.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class socialwiki_node {
    /**
     * The page ID.
     *
     * @var int
     */
    public $id;

    /**
     * The subwiki ID.
     *
     * @var int
     */
    public $swid;

    /**
     * Content showing page title and authors name.
     *
     * @var string
     */
    public $content;

    /**
     * An array of children nodes.
     *
     * @var socialwiki_node[]
     */
    public $children = array();

    /**
     * The parent nodes ID.
     *
     * @var int
     */
    public $parent;

    /**
     * A list of the peers that have liked the node's page.
     *
     * @var sdtClass[]
     */
    public $peerlist = array();

    /**
     * A count of the number of likes the node's page has.
     *
     * @var int
     */
    public $trustvalue;

    /**
     * Create a new node.
     *
     * @param stdClass $page
     */
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

    /**
     * Compute the trust using likes.
     *
     * @param stdClass $page The node's page.
     */
    private function compute_trust($page) {
        $this->peerlist = socialwiki_get_page_likes($page->id, $page->subwikiid);
        $this->trustvalue = count($this->peerlist); // Set default trust value to popularity.
    }

    /**
     * Set the content in the node.
     * Requires trust to be already computed (above)!
     *
     * @param stdClass $page The node's page.
     */
    private function set_content($page) {
        Global $PAGE, $CFG, $OUTPUT;
        // Buttons to minimize and collapse.
        $this->content = html_writer::start_tag('span', array('id' => "bgroup$this->id", 'class' => 'btngroup'));
            $this->content .= html_writer::start_tag('img', array('title' => 'Minimize', 'id' => "hid$this->id",
                'src' => $OUTPUT->pix_url('t/less'), 'class' => 'hider', 'value' => $this->id));
            $this->content .= html_writer::end_tag('img');
            $this->content .= html_writer::start_tag('img', array('title' => 'Collapse', 'id' => "cop$this->id",
                'src' => $OUTPUT->pix_url('t/up'), 'class' => 'collapser', 'value' => $this->id));
            $this->content .= html_writer::end_tag('img');
        $this->content .= html_writer::end_tag('span');

        // Title, user and date.
        $user = socialwiki_get_user_info($page->userid);
        $this->content .= html_writer::start_tag('span', array('id' => 'content' . $this->id));
            $this->content .= html_writer::start_tag('span', array('class' => 'titletext'));
            $this->content .= html_writer::link("$CFG->wwwroot/mod/socialwiki/view.php?pageid=$page->id",
                    "$page->title ID: $page->id", array('style' => 'padding:0px;'));
            $this->content .= html_writer::end_tag('span');
            $userlink = mod_socialwiki_renderer::makeuserlink($user->id, $PAGE->cm->id, $page->subwikiid);
            $this->content .= html_writer::link($userlink->out(false), fullname($user))
                    . "&nbsp; " . socialwiki_format_time($page->timecreated);
        $this->content .= html_writer::end_tag('span');
    }

    /**
     * Adds a child node.
     *
     * @param socialwiki_node $child The node to add as a child.
     */
    public function add_child($child) {
        $this->children[] = $child;
    }

    /**
     * Print out the node as an HTML list and continue with the children.
     *
     * @return string
     */
    public function to_html_list() {
        $branch = "<li><div class='tagcloud' rel='$this->trustvalue'>$this->content</div>";
        if (!empty($this->children)) {
            $branch .= "<ul id='$this->id'>";
            foreach ($this->children as $child) {
                $branch .= $child->to_html_list(); // Recursively display children.
            }
            $branch .= '</ul>';
        }

        $branch .= '</li>';
        return $branch;
    }

    /**
     * Builds up the peer list from children.
     *
     * @return stdClass[]
     */
    public function list_peers_rec() {
        $plist = $this->peerlist; // Arrays copied by value (a bit deeper than shallow copy!).
        foreach ($this->children as $child) {
            $plist = array_merge($plist, $child->list_peers_rec());
        }
        return $plist;
    }
}

/**
 * SocialWiki Tree Class.
 *
 * @package    mod_socialwiki
 * @copyright  2015 NMAI-lab
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class socialwiki_tree {
    /**
     * An array of socialwiki_nodes.
     *
     * @var socialwiki_node[]
     */
    public $nodes = array();

    /**
     * All the nodes with no parent.
     *
     * @var socialwiki_node[]
     */
    public $roots = array();

    /**
     * Build an array of nodes.
     *
     * @param stdClass[] $pages
     */
    public function build_tree($pages) {
        foreach ($pages as $page) {
            $this->add_node($page);
        }
        $this->add_children();
    }

    /**
     * Add a node to the nodes array.
     *
     * @param stdClass $page The page to add as a node.
     */
    public function add_node($page) {
        $this->nodes['l' . $page->id] = new socialwiki_node($page);
    }

    /**
     * Add the children arrays to nodes.
     */
    public function add_children() {
        // If the array has a parent add it to the parents child array.
        foreach ($this->nodes as $node) {
            if ($node->parent != -1) {
                if (isset($this->nodes[$node->parent])) {
                    $parent = $this->nodes[$node->parent];
                    $parent->add_child($node);
                } else {
                    // TODO: include a fictitious node? problem: lineage is broken.
                    // for now: just create another root.
                    $this->roots[] = $node;
                }
            } else { // Root node.
                $this->roots[] = $node; // Add to list of root nodes.
            }
        }
    }

    /**
     * Display the full tree as an HTML list with a horizontal scroll
     */
    public function display($pageid = -1) {
        Global $USER;
        // Add radio buttons to compare versions if there is more than one version.
        $compare = "";
        if (count($this->nodes) > 1) {
            foreach ($this->nodes as $node) {
                $node->content .= '<span id="comp' . $node->id . '" style="display:block">';
                $node->content .= $this->choose_from_radio(substr($node->id, 1), 'compare')
                        . $this->choose_from_radio(substr($node->id, 1), 'comparewith');
                if ($node->id == 'l' . $pageid) { // Current page.
                    $node->content .= "<br/>" . get_string('viewcurrent', 'socialwiki');
                }
                $node->content .= "</span>";
            }

            $compare .= html_writer::start_tag('form', array('action' => new moodle_url('/mod/socialwiki/diff.php'),
                'method' => 'get', 'id' => 'diff', 'class' => 'socialwiki-form-center'));
            if ($pageid != -1) {
                $compare .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pageid', 'value' => $pageid));
            }
            $compare .= html_writer::empty_tag('input', array(
                'type'  => 'submit',
                'id'    => 'comparebtn',
                'class' => 'socialwiki-form-button',
                'value' => get_string('comparesel', 'socialwiki')));
            $compare .= html_writer::end_tag('form');
        }

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

        echo $treeul . $compare;

        // Peer info from each author.
        echo '<div id="peer-info" style="display:none"><ul>';
        foreach ($allpeerset as $p) {
            $peerarray = socialwiki_peer::socialwiki_get_peer($p, $swid, $USER->id)->to_array();
            echo '<li>';
            foreach ($peerarray as $k => $v) {
                echo "<$k>$v</$k>";
            }
            echo '</li>';
        }
        echo '</ul></div>';

        return count($this->nodes);
    }

    /**
     * Given an array of values, creates a group of radio buttons to be part of a form.
     *
     * @param int $value   The page ID value.
     * @param string $name The radio button name.
     * @return string HTML
     */
    private function choose_from_radio($value, $name = 'unnamed') {
        $output = "<span class='radiogroup $name'>";
        $output .= "<input form = 'diff' name='$name' type='radio' value='$value'";
        $output .= '</span>';
        return $output;
    }
}