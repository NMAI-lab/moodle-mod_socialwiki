$(".hider").click(function () {
    var img = $("#hid"+($(this).attr("value")));
    
    if (img.attr("title") === "Minimize") {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "more");
        img.attr("title", "Maximize");
        $("#content"+($(this).attr("value"))).css("display", "none");
        $("#comp"+($(this).attr("value"))).css("display", "none");
        if ($("#"+($(this).attr("value"))))
            $("#"+($(this).attr("value"))).css("margin-top", "-5px");
    } else {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "less");
        img.attr("title", "Minimize");
        $("#content"+($(this).attr("value"))).css("display", "initial");
        $("#comp"+($(this).attr("value"))).css("display", "block");
        if ($("#"+($(this).attr("value"))))
            $("#"+($(this).attr("value"))).css("margin-top", "0");
    }
});

$(".collapser").click(function () {
    var img = $("#cop"+($(this).attr("value")));
    
    if (img.attr("title") === "Collapse") {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 2) + "down");
        img.attr("title", "Expand");
        $("#content"+($(this).attr("value"))).css("display", "none");
        $("#hid"+($(this).attr("value"))).css("display", "none");
        if ($("#comp"+($(this).attr("value"))))
            $("#comp"+($(this).attr("value"))).css("display", "none");
        if ($("#"+($(this).attr("value"))))
            $("#"+($(this).attr("value"))).css("display", "none");
    } else {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "up");
        img.attr("title", "Collapse");
        $("#hid"+($(this).attr("value"))).css("display", "inline");
        if ($("#hid"+($(this).attr("value"))).title !== "Maximize") {
            $("#content"+($(this).attr("value"))).css("display", "block");
            if ($("#comp"+($(this).attr("value"))))
                $("#comp"+($(this).attr("value"))).css("display", "block");
        }
        if ($("#"+($(this).attr("value"))))
            $("#"+($(this).attr("value"))).css("display", "block");
    }
});