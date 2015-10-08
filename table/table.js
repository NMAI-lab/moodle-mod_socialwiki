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

/**
 * Implements a DataTable on any HTML tables on the page.
 */
$(document).ready(function () {
    // Build the Datatables from HTML tables.
    $('.datatable.typev').DataTable({
        "scrollX": true,
        "scrollY": "220px",
        "scrollCollapse": true,
        "info": false,
        "paging": false,
        "columnDefs": [{"type": "time-ago","targets": 2}, {"type": "contrib","targets": [1, 5]}]
    });
    $('.datatable.typeu').DataTable({
        "scrollX": true,
        "scrollY": "220px",
        "scrollCollapse": true,
        "info": false,
        "paging": false
    });
    $('.datatable.typeg').DataTable({
        "scrollX": true,
        "scrollY": "220px",
        "scrollCollapse": true,
        "info": false,
        "paging": false
    });
});

/**
 * Sort function for updated time ago column.
 *
 * @param string d The text in the box.
 * @returns string
 */
$.fn.dataTable.ext.type.order['time-ago-pre'] = function (d) {
    var w = d.split(".");
    if (w.length === 1) {
        return d;
    } else {
        return parseInt(d.split(".")[1]);
    }
};

/**
 * Sort function for contributor column.
 *
 * @param string d The text in the box.
 * @returns string
 */
$.fn.dataTable.ext.type.order['contrib-pre'] = function (d) {
    var w = d.split(">")[1].split(" ");
    var l = parseInt(w[w.length -2]);
    if (!isNaN(l)) {
        return String.fromCharCode(l+150) + w;
    } else {
        return w;
    }
};
