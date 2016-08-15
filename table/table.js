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
 * Script for tables.
 *
 * @package   mod_socialwiki
 * @copyright 2015 NMAI-lab
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * Implements a DataTable on any HTML tables on the page.
 */
$(function() {
    // Build the Datatables from HTML tables.
    $('.datatable.typev').DataTable({  // Version Table.
        "scrollY": 220,
        "scrollX": true,
        "scrollCollapse": true,
        "info": false,
        "paging": false,
        "columnDefs": [{"type": "time-ago", "targets": 2}, {"type": "contrib", "targets": [1, 5]}]
    });
    $('.datatable.typeu').DataTable({  // User Table.
        "scrollY": 220,
        "scrollX": true,
        "scrollCollapse": true,
        "info": false,
        "paging": false,
        "columnDefs": [{"type": "distance", "targets": 4}]
    });
    $('.datatable.typeg').DataTable({  // General Table (used for Topic).
        "scrollY": 220,
        "scrollX": true,
        "scrollCollapse": true,
        "info": false,
        "paging": false
    });

    // Allow resizing of tables.
    $('.dataTables_scrollBody').each(function () {
        $(this).css({
            'height': ($(this).height()),
            'max-height': $($(this).children()[0]).height() + 20
        });
    });
});

/*
 * Sort function for updated time ago column.
 *
 * @param {String} d The text in the box
 * @returns {String}
 */
$.fn.dataTable.ext.type.order['time-ago-pre'] = function (d) {
    var val = parseInt(d.split('"')[1]);
    return (isNaN(val) ? d : val);
};

/*
 * Sort function for contributor column.
 *
 * @param {String} d The text in the box
 * @returns {String}
 */
$.fn.dataTable.ext.type.order['contrib-pre'] = function (d) {
    var w = d.split(">")[1].split(" ");
    var l = parseInt(w[w.length - 2]);
    if (!isNaN(l)) {
        return String.fromCharCode(l + 150) + w;
    } else {
        return w.join("");
    }
};

/*
 * Sort function for social distance column.
 *
 * @param {String} d The text in the box
 * @returns {String}
 */
$.fn.dataTable.ext.type.order['distance-pre'] = function (d) {
    var dist = parseInt(d[13]);
    return ((dist === 1) ? 9 : dist);
};