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
 * Generic & abstract parser functions & skeleton. It has some functions & generic stuff.
 *
 * @author  Josep Arús
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_socialwiki
 */
abstract class socialwiki_markup_parser extends socialgeneric_parser {

    protected $prettyprint = false;
    protected $printable = false;
    // Page ID.
    protected $pageid;
    // Sections.
    protected $repeatedsections;
    protected $sectionediting = true;
    // Header & Table of Contents.
    protected $toc = array();
    protected $maxheaderdepth = 3;

    private $linkgeneratorcallback = array('socialparser_utils', 'socialwiki_parser_link_callback');
    private $linkgeneratorcallbackargs = array();

    /**
     * Table generator callback
     */
    private $tablegeneratorcallback = array('socialparser_utils', 'socialwiki_parser_table_callback');

    /**
     * Get real path from relative path
     */
    private $realpathcallback = array('socialparser_utils', 'socialwiki_parser_real_path');
    private $realpathcallbackargs = array();

    /**
     * Before and after parsing...
     */
    protected function before_parsing() {
        $this->toc = array();

        $this->string = preg_replace('/\r\n/', "\n", $this->string);
        $this->string = preg_replace('/\r/', "\n", $this->string);

        $this->string .= "\n\n";

        if (!$this->printable && $this->sectionediting) {
            $this->returnvalues['unparsed_text'] = $this->string;
            $this->string = $this->get_repeated_sections($this->string);
        }
    }

    protected function after_parsing() {
        if (!$this->printable) {
            $this->returnvalues['repeated_sections'] = array_unique($this->returnvalues['repeated_sections']);
        }

        $this->process_toc();

        $this->string = preg_replace("/\n\s/", "\n", $this->string);
        $this->string = preg_replace("/\n{2,}/", "\n", $this->string);
        $this->string = trim($this->string);
        $this->string .= "\n";
    }

    /**
     * Set options
     */
    protected function set_options($options) {
        parent::set_options($options);

        $this->returnvalues['link_count'] = array();
        $this->returnvalues['repeated_sections'] = array();
        $this->returnvalues['toc'] = "";

        foreach ($options as $name => $o) {
            switch ($name) {
                case 'link_callback':
                    $callback = explode(':', $o);

                    global $CFG;
                    require_once($CFG->dirroot . $callback[0]);

                    if (function_exists($callback[1])) {
                        $this->linkgeneratorcallback = $callback[1];
                    }
                    break;
                case 'link_callback_args':
                    if (is_array($o)) {
                        $this->linkgeneratorcallbackargs = $o;
                    }
                    break;
                case 'real_path_callback':
                    $callback = explode(':', $o);

                    global $CFG;
                    require_once($CFG->dirroot . $callback[0]);

                    if (function_exists($callback[1])) {
                        $this->realpathcallback = $callback[1];
                    }
                    break;
                case 'real_path_callback_args':
                    if (is_array($o)) {
                        $this->realpathcallbackargs = $o;
                    }
                    break;
                case 'table_callback':
                    $callback = explode(':', $o);

                    global $CFG;
                    require_once($CFG->dirroot . $callback[0]);

                    if (function_exists($callback[1])) {
                        $this->tablegeneratorcallback = $callback[1];
                    }
                    break;
                case 'pretty_print':
                    if ($o) {
                        $this->prettyprint = true;
                    }
                    break;
                case 'pageid':
                    $this->pageid = $o;
                    break;
                case 'printable':
                    if ($o) {
                        $this->printable = true;
                    }
                    break;
            }
        }
    }

    /**
     * Generic block rules
     */
    protected function line_break_block_rule($match) {
        return '<hr />';
    }

    protected function list_block_rule($match) {
        preg_match_all("/^\ *([\*\#]{1,5})\ *((?:[^\n]|\n(?!(?:\ *[\*\#])|\n))+)/im", $match[1], $listitems, PREG_SET_ORDER);

        return $this->process_block_list($listitems) . $match[2];
    }

    protected function nowiki_block_rule($match) {
        return socialparser_utils::h('pre', $this->protect($match[1]));
    }

    /**
     * Generic tag rules
     */
    protected function nowiki_tag_rule($match) {
        return socialparser_utils::h('tt', $this->protect($match[1]));
    }

    /**
     * Header generation
     */
    protected function generate_header($text, $level) {
        $txt = trim($text);

        if ($level - 2 <= $this->maxheaderdepth) {
            $this->toc[] = array($level - 2, $txt);
            $num = count($this->toc);
            $txt = socialparser_utils::h('a', "", array('name' => "toc-$num")) . $txt;
        }

        return socialparser_utils::h('h' . $level, $txt) . "\n\n";
    }

    /**
     * Table of contents processing after parsing
     */
    protected function process_toc() {
        if (empty($this->toc)) {
            return;
        }

        $toc = "";
        $currentsection = array(0, 0, 0);
        $i = 1;
        foreach ($this->toc as & $header) {
            switch ($header[0]) {
                case 1:
                    $currentsection = array($currentsection[0] + 1, 0, 0);
                    break;
                case 2:
                    $currentsection[1] ++;
                    $currentsection[2] = 0;
                    if ($currentsection[0] == 0) {
                        $currentsection[0] ++;
                    }
                    break;
                case 3:
                    $currentsection[2] ++;
                    if ($currentsection[1] == 0) {
                        $currentsection[1] ++;
                    }
                    if ($currentsection[0] == 0) {
                        $currentsection[0] ++;
                    }
                    break;
                default:
                    continue;
            }
            $number = "$currentsection[0]";
            if (!empty($currentsection[1])) {
                $number .= ".$currentsection[1]";
                if (!empty($currentsection[2])) {
                    $number .= ".$currentsection[2]";
                }
            }
            $toc .= socialparser_utils::h('p', $number . ". " . socialparser_utils::h('a', $header[1], array('href' => "#toc-$i")),
                    array('class' => 'socialwiki-toc-section-' . $header[0] . " socialwiki-toc-section"));
            $i++;
        }
        $this->returnvalues['toc'] = "<div class='socialwiki-toc'><p class='socialwiki-toc-title'>"
                . get_string('tableofcontents', 'socialwiki') . "</p>$toc</div>";
    }

    /**
     * List helpers
     */
    private function process_block_list($listitems) {
        $list = array();
        foreach ($listitems as $li) {
            $text = str_replace("\n", "", $li[2]);
            $this->rules($text);

            if ($li[1][0] == '*') {
                $type = 'ul';
            } else {
                $type = 'ol';
            }

            $list[] = array(strlen($li[1]), $text, $type);
        }
        $type = $list[0][2];
        return "<$type>" . "\n" . $this->generate_list($list) . "\n</$type>\n";
    }

    /**
     * List generation function from an array of array(level, text)
     */
    protected function generate_list($listitems) {
        $list = "";
        $currentdepth = 1;
        $nextdepth = 1;
        $liststack = array();
        for ($lc = 0; $lc < count($listitems) && $nextdepth; $lc++) {
            $cli = $listitems[$lc];
            $nli = isset($listitems[$lc + 1]) ? $listitems[$lc + 1] : null;

            $text = $cli[1];

            $currentdepth = $nextdepth;
            $nextdepth = $nli ? $nli[0] : null;

            if ($nextdepth == $currentdepth || $nextdepth == null) {
                $list .= socialparser_utils::h('li', $text) . "\n";
            } else if ($nextdepth > $currentdepth) {
                $nextdepth = $currentdepth + 1;

                $list .= "<li>$text\n";
                $list .= "<$nli[2]>\n";
                $liststack[] = $nli[2];
            } else {
                $list .= socialparser_utils::h('li', $text) . "\n";

                for ($lv = $nextdepth; $lv < $currentdepth; $lv++) {
                    $type = array_pop($liststack);
                    $list .= "</$type>\n</li>\n";
                }
            }
        }

        for ($lv = 1; $lv < $currentdepth; $lv++) {
            $type = array_pop($liststack);
            $list .= "</$type>\n</li>\n";
        }

        return $list;
    }

    /**
     * Table generation functions
     */
    protected function generate_table($table) {
        return call_user_func_array($this->tablegeneratorcallback, array($table));
    }

    protected function format_image($src, $alt, $caption = "", $align = 'left') {
        $src = $this->real_path($src);
        return socialparser_utils::h('div', socialparser_utils::h('p', $caption) . "<img src='$src' alt='$alt' />",
                array('class' => "socialwiki_image_$align"));
    }

    protected function real_path($url) {
        $callbackargs = array_merge(array($url), $this->realpathcallbackargs);
        return call_user_func_array($this->realpathcallback, $callbackargs);
    }

    /**
     * Link internal callback
     */
    protected function link($link, $anchor = "") {
        $link = trim($link);
        if (preg_match("/^(https?|s?ftp):\/\/.+$/i", $link)) {
            $link = trim($link, ",.?!");
            return array('content' => $link, 'url' => $link);
        } else {
            $callbackargs = $this->linkgeneratorcallbackargs;
            $callbackargs['anchor'] = $anchor;

            $link = call_user_func_array($this->linkgeneratorcallback, array($link, $callbackargs));
            if (isset($link['link_info'])) {
                $l = $link['link_info']['link'] . ' ' . $link['link_info']['pageid'];
                unset($link['link_info']['link']);
                $this->returnvalues['link_count'][$l] = $link['link_info'];
            }
            return $link;
        }
    }

    /**
     * Format links
     */
    protected function format_link($text) {
        $matches = array();
        if (preg_match("/^([^\|]+)\|(.+)$/i", $text, $matches)) {
            $link = $matches[1];
            $content = trim($matches[2]);
            if (preg_match("/(.+)#(.*)/is", $link, $matches)) {
                $link = $this->link($matches[1], $matches[2]);
            } else if ($link[0] == '#') {
                $link = array('url' => "#" . urlencode(substr($link, 1)));
            } else {
                $link = $this->link($link);
            }

            $link['content'] = $content;
        } else {
            $link = $this->link($text);
        }

        if (isset($link['new']) && $link['new']) {
            $options = array('class' => 'socialwiki-newentry');
        } else {
            $options = array();
        }

        $link['content'] = $this->protect($link['content']);
        $link['url'] = $this->protect($link['url']);

        $options['href'] = $link['url'];

        if ($this->printable) {
            $options['href'] = '#'; // No target for the link.
        }
        return array($link['content'], $options);
    }

    /**
     * Section editing
     */
    public function get_section($header, $text, $clean = false) {
        if ($clean) {
            $text = preg_replace('/\r\n/', "\n", $text);
            $text = preg_replace('/\r/', "\n", $text);
            $text .= "\n\n";
        }

        preg_match("/(.*?)(=\ *\Q$header\E\ *=*\ *\n.*?)((?:\n=[^=]+.*)|$)/is", $text, $match);
        if (!empty($match)) {
            return array($match[1], $match[2], $match[3]);
        } else {
            return false;
        }
    }

    protected function get_repeated_sections(&$text, $repeated = array()) {
        $this->repeatedsections = $repeated;
        return preg_replace_callback($this->blockrules['header']['expression'],
                array($this, 'get_repeated_sections_callback'), $text);
    }

    protected function get_repeated_sections_callback($match) {
        $num = strlen($match[1]);
        $text = trim($match[2]);
        if ($num == 1) {
            if (in_array($text, $this->repeatedsections)) {
                $this->returnvalues['repeated_sections'][] = $text;
                return $text . "\n";
            } else {
                $this->repeatedsections[] = $text;
            }
        }

        return $match[0];
    }
}
