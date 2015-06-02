
// Let page load

// todo: how to make one such fn for each id in the table? selector of onclick function should be more general that a specific id.
$(document).ready(function()
{
    // Click trigger for image
    $("#unlikelink").click(function()
    {
        // get url [, no data], success
        $.get("likeaj.php?pageid="+pageid , function(data)
        {
            $("#unlikelink").hide();
	    $("#likelink").show();
            $("#numlikes").text(data + ((data == 1) ? ' like':' likes'));
        });
    });
    $("#likelink").click(function()
    {
        // get url [, no data], success
        $.get("likeaj.php?pageid="+pageid , function(data)
        {
            $("#likelink").hide();
	    $("#unlikelink").show();
	    $("#numlikes").text(data + ((data == 1) ? ' like':' likes'));
        });
    });
});
