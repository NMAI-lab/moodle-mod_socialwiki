// Let page load.
$(document).ready(function () {
    $(".socialwiki-likebutton").click(function () {
        // Get url [, no data], success.
        $.get("likeajax.php" + options, function (data) {
            var btnimg = $(".socialwiki-likebutton").children("img");
            var url = btnimg.attr("other");
            btnimg.attr("other", btnimg.attr("src"));
            btnimg.attr("src", url);
            var btntxt = $(".socialwiki-likebutton").children("span");
            var swap = btntxt.attr("other");
            btntxt.attr("other", btntxt.html());
            btntxt.html(swap);
            $("#numlikes").text(data + ((data == 1) ? ' like' : ' likes'));
        });
    });
});
