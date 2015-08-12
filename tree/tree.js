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

$(document).ready(function() {
    if (/view=2/.test(document.URL)) { // Only in view 'popular'.
        $('.tagcloud').tagcloud();
    }
    
    /**
     * Double scroll bar above and below the area.
     */
    var element = document.getElementById('doublescroll');
    var scrollbar = document.createElement('div');
    scrollbar.appendChild(document.createElement('div'));
    scrollbar.style.overflow = 'auto';
    scrollbar.style.overflowY = 'hidden';
    scrollbar.style.width = element.width;
    scrollbar.firstChild.style.width = element.scrollWidth + 'px';
    scrollbar.firstChild.style.paddingTop = '10px';
    scrollbar.onscroll = function () {
        element.scrollLeft = scrollbar.scrollLeft;
    };
    element.parentNode.insertBefore(scrollbar, element);

    /**
     * Enable the compare button if 2 nodes have been selected.
     */
    var compare = false;
    var comparewith = false;
    $('input[type="submit"]#comparebtn').prop('disabled', true);
    $('input[type=radio]').change(function() {
        var name = $(this).attr("name");
        if (name === 'compare') {
            compare = true;
        }
        else if (name === 'comparewith') {
            comparewith = true;
        }
        if (compare && comparewith) {
            $('input[type="submit"]#comparebtn').prop('disabled', false);
        }
    });

    /**
     * The Hide Button.
     */
    $(".hider").click(function () {
        var img = $("#hid" + ($(this).attr("value")));

        if (img.attr("title") === "Minimize") {
            img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "more");
            img.attr("title", "Maximize");
            $("#bgroup" + ($(this).attr("value"))).css("left", "0");
            $("#bgroup" + ($(this).attr("value"))).css("margin-right", "0");
            $("#content" + ($(this).attr("value"))).css("display", "none");
            $("#comp" + ($(this).attr("value"))).css("display", "none");
        } else {
            img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "less");
            img.attr("title", "Minimize");
            $("#bgroup" + ($(this).attr("value"))).css("left", "50%");
            $("#bgroup" + ($(this).attr("value"))).css("margin-right", "29px");
            $("#content" + ($(this).attr("value"))).css("display", "initial");
            $("#comp" + ($(this).attr("value"))).css("display", "block");
        }
        scrollbar.firstChild.style.width = element.scrollWidth + 'px';
    });

    /**
     * The Collapse Button.
     */
    $(".collapser").click(function () {
        var img = $("#cop" + ($(this).attr("value")));

        if (img.attr("title") === "Collapse") {
            img.attr("src", img.attr("src").substring(0, img.attr("src").length - 2) + "down");
            img.attr("title", "Expand");
            $("#hid" + ($(this).attr("value"))).css("display", "none");
            if ($("#hid" + ($(this).attr("value"))).attr("title") === "Minimize") {
                $("#bgroup" + ($(this).attr("value"))).css("left", "0");
                $("#bgroup" + ($(this).attr("value"))).css("margin-right", "0");
                $("#content" + ($(this).attr("value"))).css("display", "none");
                if ($("#comp" + ($(this).attr("value")))) {
                    $("#comp" + ($(this).attr("value"))).css("display", "none");
                }
            }
            if ($("#" + ($(this).attr("value")))) {
                $("#" + ($(this).attr("value"))).css("display", "none");
            }
        } else {
            img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "up");
            img.attr("title", "Collapse");
            $("#hid" + ($(this).attr("value"))).css("display", "inline");
            if ($("#hid" + ($(this).attr("value"))).attr("title") === "Minimize") {
                $("#bgroup" + ($(this).attr("value"))).css("left", "50%");
                $("#bgroup" + ($(this).attr("value"))).css("margin-right", "29px");
                $("#content" + ($(this).attr("value"))).css("display", "block");
                if ($("#comp" + ($(this).attr("value")))) {
                    $("#comp" + ($(this).attr("value"))).css("display", "block");
                }
            }
            if ($("#" + ($(this).attr("value")))) {
                $("#" + ($(this).attr("value"))).css("display", "block");
            }
        }
        scrollbar.firstChild.style.width = element.scrollWidth + 'px';
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
    "use strict";

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
