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
    $('.datatable').DataTable({
        "scrollX": true,
        "scrollY": "220px",
        "scrollCollapse": true,
        "info": false,
        "paging": false,
        "columnDefs": [{"type": "time-ago","targets": 2}]
    });
});

/**
 * Sort function for updated time ago column.
 *
 * @param string d The text in the box.
 * @returns string
 */
$.fn.dataTable.ext.type.order['time-ago-pre'] = function (d) {
    var w = d.split(" ");
    if (d.indexOf("second") > -1) {
        return w[0];
    } else if (d.indexOf("minute") > -1) {
        return w[0] * 100;
    } else if (d.indexOf("hour") > -1) {
        return w[0] * 10000;
    } else if (d.indexOf("day") > -1) {
        return w[0] * 1000000;
    } else if (d.indexOf("month") > -1) {
        return w[0] * 1000000000;
    } else if (d.indexOf("year") > -1) {
        return w[0] * 100000000000;
    }
    return d;
};
