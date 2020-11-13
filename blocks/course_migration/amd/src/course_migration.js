/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
	
	function migrate(apiurl, courseid, a) {
		$.post(
		    apiurl,
            {id: courseid, a: a },
            function(response) {
            	// just reload the page for now :/
            	window.location.reload(true); 
            },
            "json"
        );
	}

    function init(apiurl, courseid, a) {
    	$(".course_mig_link").on("click", function(e){
    		migrate(apiurl, courseid, a);
    	});
    }
	
    return {
    	init: function(apiurl, courseid, a) {
            init(apiurl, courseid, a);
        }
    };

});