/* jshint ignore:start */
define(['local_magisterelib/nestable', 'local_magisterelib/tooltipster', 'jqueryui'], function(){

    function init(){
        var dd_start_parent = null;

        $(".dd").nestable({
            maxDepth:3,
            handleClass: "dd-itemdiv",
            expandBtnHTML: "",
            collapseBtnHTML:"",
            group: 1,
            onDragStart: function(l, e, p){
                var handleElm = $(e).find(".dd-div").first();
                var pos = handleElm.offset();
                var w = handleElm.width();
                var h = handleElm.height();

                // d&d only on the cross-handle
                if(p.left < pos.left || p.left > pos.left + w
                    || p.top < pos.top || p.top > pos.top + h){
                    return false;
                }

                dd_start_parent = e.parents("ol:first").parent("li").children(".dd-itemdiv:first");
            },
        });

        $(".ishidden").parents("div.dd-itemdiv").addClass("hideClass");

        $(".dd").on( "click", ".hide", function() {
            if ($(this).hasClass("ishidden"))
            {
                $(this).toggleClass("ishidden",false);
                $(this).addClass("fa-eye");
                $(this).removeClass("fa-eye-slash");
                $(this).tooltipster("content",$('<span>Masquer l\'activité</span>'));
                $(this).parents("div.dd-itemdiv").removeClass("hideClass");

            }else{
                $(this).toggleClass("ishidden",true);
                $(this).removeClass("fa-eye");
                $(this).addClass("fa-eye-slash");
                $(this).tooltipster("content",$('<span>Afficher l\'activité</span>'));
                $(this).parents("div.dd-itemdiv").addClass("hideClass");

            }
        });

        $("#submit").on("click", function(e)
        {
            var list = serialize_tree($(".block_list_activities"));

            $("#treedata").val(JSON.stringify(list));

        });


        var params = {
            maxWidth: 620,
            position: "bottom-right",
            timer: 3000
        };

        params.content = $("<span>Masquer l'activité</span>");
        $(".hide:not(.ishidden)").tooltipster(params);

        params.content = $("<span>Afficher l'activité</span>");
        $(".ishidden").tooltipster(params);

        params.content = $("<span>Déplacer l'activité</span>");
        $(".move").tooltipster(params);

    }


    function serialize_tree(node)
    {
        var weight_ids = [];
        var not_displayed_ids = [];


        var step = function(level, parentid) {
            var array = [],
                items = level.children("li");


            items.each(function() {
                var item  =  $(this).data();



                var not_visible = $(this).find(".hide:first").hasClass("ishidden");

                if(not_visible){
                    not_displayed_ids.push(item.id);
                }
                item.parentid = (parentid ? parentid : null);

                if(item.id > 0){
                    weight_ids.push(item.id);
                }

            });
            return array;
        };

        var tree = {
            weight_ids: weight_ids,
            not_displayed_ids: not_displayed_ids
        };


        node.find("#dd>ol").each(function(){
            step($(this));
        });

        return tree;
    }

    return {
        init: function(){
            init();
        }
    };
});