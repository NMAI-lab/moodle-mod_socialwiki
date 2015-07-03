var scrollbar;
var element = document.getElementById('doublescroll');
DoubleScroll();

// Hide Button.
$(".hider").click(function () {
    var img = $("#hid" + ($(this).attr("value")));

    if (img.attr("title") === "Minimize") {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "more");
        img.attr("title", "Maximize");
        $("#bgroup" + ($(this).attr("value"))).css("left", "0");
        $("#content" + ($(this).attr("value"))).css("display", "none");
        $("#comp" + ($(this).attr("value"))).css("display", "none");
    } else {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "less");
        img.attr("title", "Minimize");
        $("#bgroup" + ($(this).attr("value"))).css("left", "40%");
        $("#content" + ($(this).attr("value"))).css("display", "initial");
        $("#comp" + ($(this).attr("value"))).css("display", "block");
    }
    scrollbar.firstChild.style.width = element.scrollWidth + 'px';
});

// Collapse Button.
$(".collapser").click(function () {
    var img = $("#cop" + ($(this).attr("value")));

    if (img.attr("title") === "Collapse") {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 2) + "down");
        img.attr("title", "Expand");
        $("#bgroup" + ($(this).attr("value"))).css("left", "0");
        $("#content" + ($(this).attr("value"))).css("display", "none");
        $("#hid" + ($(this).attr("value"))).css("display", "none");
        if ($("#comp" + ($(this).attr("value")))) {
            $("#comp" + ($(this).attr("value"))).css("display", "none");
        }
        if ($("#" + ($(this).attr("value")))) {
            $("#" + ($(this).attr("value"))).css("display", "none");
        }
    } else {
        img.attr("src", img.attr("src").substring(0, img.attr("src").length - 4) + "up");
        img.attr("title", "Collapse");
        $("#bgroup" + ($(this).attr("value"))).css("left", "40%");
        $("#hid" + ($(this).attr("value"))).css("display", "inline");
        if ($("#hid" + ($(this).attr("value"))).title !== "Maximize") {
            $("#content" + ($(this).attr("value"))).css("display", "block");
            if ($("#comp" + ($(this).attr("value")))) {
                $("#comp" + ($(this).attr("value"))).css("display", "block");
            }
        }
        if ($("#" + ($(this).attr("value")))) {
            $("#" + ($(this).attr("value"))).css("display", "block");
        }
    }
    scrollbar.firstChild.style.width = element.scrollWidth + 'px';
});

// Double scroll bar above and below the area.
function DoubleScroll() {
    scrollbar = document.createElement('div');
    scrollbar.appendChild(document.createElement('div'));
    scrollbar.style.overflow = 'auto';
    scrollbar.style.overflowY = 'hidden';
    scrollbar.style.width = element.width;
    scrollbar.firstChild.style.width = element.scrollWidth + 'px';
    scrollbar.firstChild.style.paddingTop = '1px';
    scrollbar.firstChild.appendChild(document.createTextNode('\xA0'));
    scrollbar.onscroll = function () {
        element.scrollLeft = scrollbar.scrollLeft;
    };
    element.onscroll = function () {
        scrollbar.scrollLeft = element.scrollLeft;
    };
    element.parentNode.insertBefore(scrollbar, element);
}