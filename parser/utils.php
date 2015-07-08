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
 * Parser utils and default callbacks.
 *
 * @author Josep Arús
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_socialwiki
 */

require_once($CFG->dirroot . "/lib/outputcomponents.php");

class socialparser_utils {

    public static function h($tag, $text = null, $options = array(), $escapetext = false) {
        $tag = htmlentities($tag, ENT_COMPAT, 'UTF-8');
        if (!empty($text) && $escapetext) {
                $text = htmlentities($text, ENT_COMPAT, 'UTF-8');
        }
        return html_writer::tag($tag, $text, $options);
    }

    /**
     * Default link generator
     */
    public static function socialwiki_parser_link_callback($link, $options) {
        $l = urlencode($link);
        if (!empty($options['anchor'])) {
            $l .= "#".urlencode($options['anchor']);
        }
        return array('content' => $link, 'url' => "http://".$l);
    }

    /**
     * Default table generator
     */
    public static function socialwiki_parser_table_callback($table) {
        $html = "";
        $headers = $table[0];
        $columncount = count($headers);
        $headerhtml = "";
        foreach ($headers as $h) {
            $text = trim($h[1]);
            if ($h[0] == 'header') {
                $headerhtml .= "\n".self::h('th', $text)."\n";
                $hasheaders = true;
            } else if ($h[0] == 'normal') {
                $headerhtml .= "\n".self::h("td", $text)."\n";
            }
        }
        $headerhtml = "\n".self::h('tr', $headerhtml)."\n";
        $bodyhtml = "";
        if (isset($hasheaders)) {
            $html = "\n".self::h('thead', $headerhtml)."\n";
        } else {
            $bodyhtml .= $headerhtml;
        }

        array_shift($table);
        foreach ($table as $row) {
            $htmlrow = "";
            for ($i = 0; $i < $columncount; $i++) {
                $text = "";
                if (!isset($row[$i])) {
                    $htmlrow .= "\n".self::h('td', $text)."\n";
                } else {
                    $text = trim($row[$i][1]);
                    if ($row[$i][0] == 'header') {
                        $htmlrow .= "\n".self::h('th', $text)."\n";
                    } else if ($row[$i][0] == 'normal') {
                        $htmlrow .= "\n".self::h('td', $text)."\n";
                    }
                }
            }
            $bodyhtml .= "\n".self::h('tr', $htmlrow)."\n";
        }

        $html .= "\n".self::h('tbody', $bodyhtml)."\n";
        return "\n".self::h('table', $html)."\n";
    }

    /**
     * Default path converter
     */
    public static function socialwiki_parser_real_path($url) {
        return $url;
    }
}

