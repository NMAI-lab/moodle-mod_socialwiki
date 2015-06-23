//patterns to determine the view mode
var pattern1=/option=1/;  //tree
var pattern2=/option=2/; // list
var pattern3=/option/;
var pattern4=/option=3/; //popular

$(document).ready(function() {

	$("#table_searchresults").dataTable({
            "sScrollY": "200px",
            "bPaginate": false,
            "bScrollCollapse": true,
            });
	
	$.fn.tagcloud.defaults = {
  		size: {start: 7, end: 26, unit: 'pt'},
  		color: {start: '#000', end: '#000'}
		};

	//alert("about to tag the cloud");
	if(pattern4.test(document.URL)){ // only in view 'popular'
	  	$('#doublescroll .tagcloud').tagcloud(); //(doublescroll is the box containing the socialwikitree)
	  }
});
