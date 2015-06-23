$.fn.dataTable.ext.type.order['time-ago-pre'] = function ( d ) {
    var words = d.split(" ");
    if (d.indexOf("second") > -1) {
        return words[0];
    }
    else if (d.indexOf("minute") > -1) {
        return words[0]*100;
    }
    else if (d.indexOf("hour") > -1) {
        return words[0]*10000;
    }
    else if (d.indexOf("day") > -1) {
        return words[0]*1000000;
    }
    else if (d.indexOf("month") > -1) {
        return words[0]*1000000000;
    }
    else if (d.indexOf("year") > -1) {
        return words[0]*100000000000;
    }
    return d;
};

$(document).ready(function () {
    var table = $('.datatable').DataTable({
        "scrollY": true,
        "scrollX": true,
        "info": false,
        "paging": false,
        "columnDefs": [ {
            "type": "time-ago",
            "targets": 2
        } ]
    });
    if (table.page.len() < 12) {
        $(this).find(".dataTables_filter").hide();
    }
});
