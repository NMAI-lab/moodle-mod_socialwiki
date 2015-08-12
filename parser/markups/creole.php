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
 * Creole parser implementation.
 *
 * 
 * @package mod_socialwiki
 * @author Josep Arús
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require_once("wikimarkup.php");

class creole_parser extends socialwiki_markup_parser {

    protected $blockrules = array(
        'nowiki' => array(
            'expression' => "/^\{\{\{(.*?)\}\}\}/ims",
            'tags' => array(),
            'token' => array('{{{', '}}}')
        ),
        'header' => array(
            'expression' => "/^\ *(={1,6})\ *(.+?)=*\ *$/ims",
            'tags' => array(), // None.
            'token' => '='
        ),
        'table' => array(
            'expression' => "/^(?:\|.*?\|\ *\n)+/ims"
        ),
        'line_break' => array(
            'expression' => "/^----\s*$/im",
            'token' => '----',
            'tags' => array()
        ),
        'list' => array(
            'expression' => "/((?:^\ *[\*#][^\*#]\ *.+?)(?:^\ *[\*#]{1,5}\ *.+?)*)(\n\s*(?:\n|<(?:h\d|pre|table|tbody|thead|tr|th|td|ul|li|ol|hr)))/ims",
            'tags' => array(),
            'token' => array('*', '#')
        ),
        'paragraph' => array(
            'expression' => "/^\ *((?:<(?!\ *\/?(?:h\d|pre|table|tbody|thead|tr|th|td|ul|li|ol|hr)\ *\/?>)|[^<\s]).+?)\n\s*\n/ims",
            // Not specified -> all tags (null or unset).
            'tag' => 'p'
        )
    );
    protected $tagrules = array(
        'nowiki' => array(
            'expression' => "/\{\{\{(.*?)\}\}\}/is",
            'token' => array('{{{', '}}}')
        ),
        'image' => array(
            'expression' => "/\~?\{\{(.+)\|(.+)\}\}/i",
            'tags' => array(),
            'token' => array('{{', '|Alt}}')
        ),
        'link' => array(
            'expression' => "/\~?\[\[(.+?)\]\]/is",
            'tag' => 'a',
            'token' => array('[[', ']]')
        ),
        'url' => array(
            'expression' => "/\~?(?<!=\")((?:https?|ftp):\/\/[^\s\n]+[^,\.\?!:;\"\'\n\ ])/is",
            'tag' => 'a',
            'token' => "http://"
        ),
        'line_break' => array(
            'expression' => "/\\\\\\\\/",
            'tag' => 'br',
            'simple' => true,
            'token' => '----'
        ),
        'bold' => array(
            'expression' => "/\*\*(.*?)(?:\*\*|$)/is",
            'tag' => 'strong',
            'token' => array('**', '**')
        ),
        'italic' => array(
            'expression' => "#(?<!http:|https:|ftp:)//(.+?)(?<!http:|https:|ftp:)//#is",
            'tag' => 'em',
            'token' => array('//', '//')
        )
    );

    /**
     * Block hooks
     */
    protected function before_parsing() {
        $this->string = htmlspecialchars($this->string);
        parent::before_parsing();
    }

    protected function header_block_rule($match) {

        $num = strlen($match[1]);

        $text = trim($match[2]);

        $text = preg_replace("/\s*={1,$num}$/im", "", $text);

        return $this->generate_header($text, $num);
    }

    /**
     * Table generation
     */
    protected function table_block_rule($match) {

        $rows = explode("\n", $match[0]);
        $table = array();
        foreach ($rows as $r) {
            if (empty($r)) {
                continue;
            }
            $rawcells = explode("|", $r);
            $cells = array();

            array_shift($rawcells);
            array_pop($rawcells);

            foreach ($rawcells as $c) {
                if (!empty($c)) {
                    if ($c[0] == "=") {
                        $type = 'header';
                        $c = substr($c, 1);
                    } else {
                        $type = 'normal';
                    }
                    $this->rules($c);
                    $cells[] = array($type, $c);
                }
            }
            $table[] = $cells;
        }

        return $this->generate_table($table);
    }

    protected function paragraph_block_rule($match) {
        $text = $match[1];
        foreach ($this->tagrules as $tr) {
            if (isset($tr['token'])) {
                if (is_array($tr['token'])) {
                    $this->escapetoken_string($text, $tr['token'][0]);
                    $this->escapetoken_string($text, $tr['token'][1]);
                } else {
                    $this->escapetoken_string($text, $tr['token']);
                }
            }
        }
        $this->escapetoken_string($text, "~");

        return $text;
    }

    /**
     * Escape token when it is "negated"
     */
    private function escapetoken_string(&$text, $token) {
        $text = str_replace("~$token", $this->protect($token), $text);
    }

    /**
     * Tag functions
     */
    protected function url_tag_rule($match) {
        if (strpos($match[0], "~") === 0) {
            return substr($match[0], 1);
        } else {
            $text = trim($match[0]);
            $options = array('href' => $text);

            return array($text, $options);
        }
    }

    protected function link_tag_rule($match) {
        $text = trim($match[1]);

        if (strpos($match[0], "~") === 0) {
            return substr($match[0], 1);
        } else {
            return $this->format_link($text);
        }
    }

    /**
     * Special treatment of // ** // ** //
     */
    protected function bold_tag_rule($match) {
        $text = $match[1];
        $this->rules($text, array('only' => array('italic')));
        if (strpos($text, "//") !== false) {
            $text = str_replace("//", $this->protect("//"), $text);
        }
        return array($text, array());
    }

    protected function image_tag_rule($match) {
        if (strpos($match[0], "~") === 0) {
            return substr($match[0], 1);
        }

        return $this->format_image($match[1], $match[2]);
    }
}
