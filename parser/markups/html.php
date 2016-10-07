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
 * HTML parser implementation. It only implements links.
 *
 * @package mod_socialwiki
 * @author Josep Arús
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require_once("wikimarkup.php");

class html_parser extends socialwiki_markup_parser {

    protected $blockrules = array();
    protected $tagrules = array(
        'link' => array(
            'expression' => "/\[\[(.+?)\]\]/is",
            'tag' => 'a',
            'token' => array("[[", "]]")
        ),
        'url' => array(
            'expression' => "/(?<!=\")((?:https?|ftp):\/\/[^\s\n]+[^,\.\?!:;\"\'\n\ ])/i",
            'tag' => 'a',
            'token' => 'http://'
        )
    );
    protected $sectionediting = true;

    public function __construct() {
        parent::__construct();

        // Headers are considered tags here.
        $headerdepth = $this->maxheaderdepth + 2;
        $this->tagrules['header'] = array('expression' => "/<\s*h([1-$headerdepth])\s*>(.+?)<\/h[1-$headerdepth]>/is");
    }

    protected function before_parsing() {
        parent::before_parsing();

        $this->rules($this->string);
    }

    /**
     * Header tag rule.
     *
     * @param array $match Header regex match.
     * @return string
     */
    protected function header_tag_rule($match) {
        // Fix to avoid users adding h1 and h2 that aren't allowed.
        if ($match[1] < 3) {
            $match[1] = 3;
        }
        return $this->generate_header($match[2], $match[1]);
    }

    /**
     * Section editing: Special for HTML Parser (It parses <h1></h1>)
     */
    public function get_section($header, $text, $clean = false) {
        if ($clean) {
            $text = preg_replace('/\r\n/', "\n", $text);
            $text = preg_replace('/\r/', "\n", $text);
            $text .= "\n\n";
        }

        $h1 = array("<\s*h1\s*>", "<\/h1>");

        preg_match("/(.*?)({$h1[0]}\s*\Q$header\E\s*{$h1[1]}.*?)((?:\n{$h1[0]}.*)|$)/is", $text, $match);

        if (!empty($match)) {
            return array($match[1], $match[2], $match[3]);
        } else {
            return false;
        }
    }

    protected function get_repeated_sections(&$text, $repeated = array()) {
        $this->repeated_sections = $repeated;
        return preg_replace_callback($this->tagrules['header'], array($this, 'get_repeated_sections_callback'), $text);
    }

    protected function get_repeated_sections_callback($match) {
        $text = trim($match[2]);

        if (in_array($text, $this->repeated_sections)) {
            $this->returnvalues['repeated_sections'][] = $text;
            return parser_utils::h('p', $text);
        } else {
            $this->repeated_sections[] = $text;
        }

        return $match[0];
    }

    /**
     * Link tag functions
     */
    protected function link_tag_rule($match) {
        return $this->format_link($match[1]);
    }

    protected function url_tag_rule($match) {
        $url = $this->protect($match[1]);
        $options = array('href' => $url);
        return array($url, $options);
    }
}
