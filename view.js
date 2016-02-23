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
 * Script for the like button.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(function() {
    var $like = $("#socialwiki-like");
    $like.submit(function (e) {
        $.get("like.ajax.php" + options, function (data) {
            if (toString.call(data) === '[object Number]') {
                var $btnimg = $like.children("button").children("img");
                var url = $btnimg.attr("other");
                $btnimg.attr("other", $btnimg.attr("src"));
                $btnimg.attr("src", url);
                var $btntxt = $like.children("button").children("span");
                var swap = $btntxt.attr("other");
                $btntxt.attr("other", $btntxt.html());
                $btntxt.html(swap);
                $("#numlikes").text(data + ((data == 1) ? ' like' : ' likes'));
            }
        });
        e.preventDefault();
    });

    var $com = $("#socialwiki-comdirection");
    $com.show();
    $com.click(function () {
        console.log('test');
        var swap = $com.attr("other");
        $com.attr("other", $com.text());
        $com.text(swap);
        $(".socialwiki-commentlist").toggleClass('reversed');
    });
});
