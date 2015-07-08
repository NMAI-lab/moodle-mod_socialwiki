// Sort function for updated time ago column.
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

$(document).ready(function () {
    // Build the Datatable from HTML table.
    var table = $('.datatable').DataTable({
        "scrollY": true,
        "scrollX": true,
        "info": false,
        "paging": false,
        "columnDefs": [{"type": "time-ago","targets": 2}]
    });
    // Only show the search if there is at least 12 results.
    if (table.page.len() < 12) {
        $(this).find(".dataTables_filter").hide();
    }
});
