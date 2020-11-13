/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {
    function init(id, ajax_url){
        var hasBeenProcess = false;
        var previousFile = "";
        $(".filepicker-filename").bind("DOMNodeInserted",function(){
            if(hasBeenProcess == true){return;}
            var fileurl = $(".filepicker-filename").find("a").attr("href");
            if(!fileurl || previousFile == fileurl){return;}
            previousFile = fileurl;
            hasBeenProcess = true;

            $.ajax({
                type: "POST",
                url: ajax_url,
                data:{
                    url: fileurl,
                    courseid: id
                },
                datatype: "json",
                success:function(response){
                    var json = JSON.parse(response);
                    if(json.type == "simple"){
                        $(".panel-complex").show();
                    }else{
                        $(".panel-complex").hide();
                    }
                    $("[name='type']").val(json.type);
                    $("#page-blocks-csv-enrol-warning").empty();
                    if(json.msg != ""){
                        $("#page-blocks-csv-enrol-warning").append("<p>"+json.msg+"</p>");
                    }
                    hasBeenProcess = false;
                },
                error:function(error){
                    console.log(error);
                }
            });
        });
    }

    return {
        init: function(id, ajax_url){
            init(id, ajax_url);
        }
    };
});