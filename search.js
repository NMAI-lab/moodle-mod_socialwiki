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
 * Script on the search page.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs the tagcloud the resize text.
 * If popular is selected from the menu dropown then resize to show them better.
 */
$(document).ready(function () {
    if (/option=3/.test(document.URL)) { // Only in view 'popular'.
        $('.tagcloud').tagcloud();
    }
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
        var opts = $.fn.tagcloud.defaults;
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
        var fontIncr = (opts.size.end - opts.size.start) / range;
        return this.each(function () {
            var weighting = $(this).attr("rel") - lowest;
            if (zerorange) { // If there's just one value, put it in the middle rather than at the bottom of the sizes range.
                $(this).css({"font-size": (opts.size.start + opts.size.end) / 2 + opts.size.unit});
            } else {
                $(this).css({"font-size": opts.size.start + (weighting * fontIncr) + opts.size.unit});
            }
        });
    };

    $.fn.tagcloud.defaults = {
        size: {start: 8, end: 18, unit: "pt"}
    };
})(jQuery);
