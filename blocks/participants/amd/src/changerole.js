/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    function init(){
        $("#roleid").on('change', function() {
           $(this).closest("form").submit();
        });
        $("#groupid").on('change', function() {
            $(this).closest("form").submit();
        });
    }

    return {
        init: function(){
            init();
        }
    };
});