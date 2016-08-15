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

$("#socialwiki-like").on('click', function (e) {
    e.preventDefault();
    var $like = $(this);
    if ($like.attr('href') == '') {
        return;
    }
    $.get("like.ajax.php" + options, function (data) {
        if (data !== 'error') {
            var $btnimg = $like.find("img");
            $btnimg.attr({
                'other': $btnimg.attr('src'),
                'src': $btnimg.attr('other')
            });
            $like.toggleClass('liked');
            $like.find("span").text(data);
        }
    });
});

$("#navigator").on('change', function() {
    $(this).find('option[value=' + this.value + ']').insertAfter($(this).find('option:eq(1)'));
    $.get("like.ajax.php" + options + "&navi=" + this.value, function (data) {
        if (data !== 'error') {
            $(".socialwiki-contributors").remove();
            $(".wikipage").replaceWith(data);
        }
    });
});

$("#socialwiki-comdirection").on('click', function () {
    var $this = $(this);
    var swap = $this.attr("other");
    $this.attr("other", $this.text());
    $this.text(swap);
    $(".socialwiki-commentlist").toggleClass('reversed');
}).show();
