$(".hider").click(function () {
    var img = document.querySelector("#hid".concat($(this).attr("value")));
    if (document.querySelector("#content".concat($(this).attr("value"))).style.display !== "none") {
        img.src = img.src.substring(0, img.src.length-4) + "more";
        img.title = "Maximize";
        document.querySelector("#content".concat($(this).attr("value"))).style.display = "none";
        document.querySelector("#comp".concat($(this).attr("value"))).style.display = "none";
    } else {
        img.src = img.src.substring(0, img.src.length-4) + "less";
        img.title = "Minimize";
        document.querySelector("#content".concat($(this).attr("value"))).style.display = null;
        document.querySelector("#comp".concat($(this).attr("value"))).style.display = "block";
    }
    
});

$(".collapser").click(function () {
    var img = document.querySelector("#cop".concat($(this).attr("value")));
    if (document.querySelector("#hid".concat($(this).attr("value"))).style.display !== "none") {
        img.src = img.src.substring(0, img.src.length-2) + "down";
        img.title = "Grow";
        document.querySelector("#content".concat($(this).attr("value"))).style.display = "none";
        document.querySelector("#hid".concat($(this).attr("value"))).style.display = "none";
        if (document.querySelector("#comp".concat($(this).attr("value"))))
            document.querySelector("#comp".concat($(this).attr("value"))).style.display = "none";
        if (document.querySelector("#".concat($(this).attr("value"))))
            document.querySelector("#".concat($(this).attr("value"))).style.display = "none";
    } else {
        img.src = img.src.substring(0, img.src.length-4) + "up";
        img.title = "Collapse";
        document.querySelector("#hid".concat($(this).attr("value"))).style.display = null;
        if (document.querySelector("#hid".concat($(this).attr("value"))).title !== "Maximize") {
            document.querySelector("#content".concat($(this).attr("value"))).style.display = null;
            if (document.querySelector("#comp".concat($(this).attr("value"))))
                document.querySelector("#comp".concat($(this).attr("value"))).style.display = "block";
        }
        
        if (document.querySelector("#".concat($(this).attr("value"))))
            document.querySelector("#".concat($(this).attr("value"))).style.display = null;
    }
});