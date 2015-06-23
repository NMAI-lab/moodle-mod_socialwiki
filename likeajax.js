
// Let page load

// todo: how to make one such fn for each id in the table? selector of onclick function should be more general that a specific id.
$(document).ready(function() {
    $(".socialwiki_likebutton").click(function() {
        // get url [, no data], success
        $.get("like.php?pageid="+pageid , function(data) {
            var btnimg = $(".socialwiki_likebutton").children("img");
            var url = btnimg.attr("other");
            btnimg.attr("other", btnimg.attr("src"));
            btnimg.attr("src", url);
            var btntxt = $(".socialwiki_likebutton").children("span");
            var swap = btntxt.attr("other");
            btntxt.attr("other", btntxt.html());
            btntxt.html(swap);
            $("#numlikes").text(data + ((data == 1) ? ' like':' likes'));
        });
    });
});
