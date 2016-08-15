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
 * Script for tree view.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";
$(function () {
    if (/view=2/.test(document.URL)) { // Only in view 'popular'.
        $('.tagcloud').tagcloud();
    }

    var d = document.getElementById('dragscroll'), lastX, lastY, down = false;
    d.style.cursor = 'move';
    d.onmousedown = function(e) {
        lastX = e.clientX;
        lastY = e.clientY;
        down = true;
        return false;
    };
    document.onmousemove = function(e) {
        if (down) {
            d.scrollLeft += (lastX - (lastX = e.clientX));
            window.scrollBy(0, lastY - (lastY = e.clientY));
            return false;
        }
    };
    document.onmouseup = function() {
        down = false;
        return false;
    };

    // Enable the compare button if 2 nodes have been selected.
    var compare = false;
    var comparewith = false;
    var comparebtn = document.getElementById('comparebtn');
    if (comparebtn !== null) {
        comparebtn.disabled = true;
    }
    var radio = document.querySelectorAll('input[type=radio]');
    for (var i = 0; i < radio.length; i++) {
        radio[i].onchange = function () {
            var name = this.getAttribute("name");
            if (name === 'compare') {
                compare = true;
            } else if (name === 'comparewith') {
                comparewith = true;
            }
            if (compare && comparewith) {
                comparebtn.disabled = false;
            }
        };
    }

    // The Hide Button.
    $(".hider").click(function () {
        var $i = $("#hid" + ($(this).attr("value")));
        var $b = $("#bgroup" + ($(this).attr("value")));

        if ($i.attr("title") === "Minimize") {
            $i.attr("src", $i.attr("src").substring(0, $i.attr("src").length - 4) + "more");
            $i.attr("title", "Maximize");
            $b.css("left", "0");
            $b.css("margin-right", "0");
            $("#content" + ($(this).attr("value"))).css("display", "none");
            $("#comp" + ($(this).attr("value"))).css("display", "none");
        } else {
            $i.attr("src", $i.attr("src").substring(0, $i.attr("src").length - 4) + "less");
            $i.attr("title", "Minimize");
            $b.css("left", "50%");
            $b.css("margin-right", "29px");
            $("#content" + ($(this).attr("value"))).css("display", "initial");
            $("#comp" + ($(this).attr("value"))).css("display", "block");
        }
    });

    // The Collapse Button.
    $(".collapser").click(function () {
        var $i = $("#cop" + ($(this).attr("value")));
        var $b = $("#bgroup" + ($(this).attr("value")));
        var $h = $("#hid" + ($(this).attr("value")));

        if ($i.attr("title") === "Collapse") {
            $i.attr("src", $i.attr("src").substring(0, $i.attr("src").length - 2) + "down");
            $i.attr("title", "Expand");
            $h.css("display", "none");
            if ($h.attr("title") === "Minimize") {
                $b.css("left", "0");
                $b.css("margin-right", "0");
                $("#content" + ($(this).attr("value"))).css("display", "none");
                if ($("#comp" + ($(this).attr("value")))) {
                    $("#comp" + ($(this).attr("value"))).css("display", "none");
                }
            }
            if ($("#" + ($(this).attr("value")))) {
                $("#" + ($(this).attr("value"))).css("display", "none");
            }
        } else {
            $i.attr("src", $i.attr("src").substring(0, $i.attr("src").length - 4) + "up");
            $i.attr("title", "Collapse");
            $h.css("display", "inline");
            if ($h.attr("title") === "Minimize") {
                $b.css("left", "50%");
                $b.css("margin-right", "29px");
                $("#content" + ($(this).attr("value"))).css("display", "block");
                if ($("#comp" + ($(this).attr("value")))) {
                    $("#comp" + ($(this).attr("value"))).css("display", "block");
                }
            }
            if ($("#" + ($(this).attr("value")))) {
                $("#" + ($(this).attr("value"))).css("display", "block");
            }
        }
    });
});

/*!
 * jquery.tagcloud.js
 * A Simple Tag Cloud Plugin for JQuery
 *
 * https://github.com/addywaddy/jquery.tagcloud.js
 * created by Adam Groves
 */
(function ($) {
    var compareWeights = function (a, b) {
        return a - b;
    };

    $.fn.tagcloud = function () {
        var tagWeights = this.map(function () {
            return $(this).attr("rel");
        });
        tagWeights = jQuery.makeArray(tagWeights).sort(compareWeights);
        var lowest = tagWeights[0];
        var highest = tagWeights.pop();
        var range = highest - lowest;
        // Adding stuff to handle range = 0.
        var zerorange = false;
        if (range === 0) {
            range = 1;
            zerorange = true; // Remember range was 0.
        }
        // Sizes.
        var size = {start: 8, end: 18, unit: "pt"};
        var fontIncr = (size.end - size.start) / range;
        return this.each(function () {
            var weighting = $(this).attr("rel") - lowest;
            if (zerorange) { // If there's just one value, put it in the middle rather than at the bottom of the sizes range.
                $(this).css({"font-size": (size.start + size.end) / 2 + size.unit});
            } else {
                $(this).css({"font-size": size.start + (weighting * fontIncr) + size.unit});
            }
        });
    };
})(jQuery);