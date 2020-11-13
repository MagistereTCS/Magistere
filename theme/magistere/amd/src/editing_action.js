define(["jquery"], function () {

    var oneditbutton = function(editingbutton){
        var div = editingbutton.parent();
        var link = div.find('a:first');

        var instancenamespan = link.find('.instancename');
        var instancename = instancenamespan.text();

        var editfield = $('<input>').attr({
            'type': 'text',
            'value': instancename,
            'class': 'editingfield'
        });

        div.prepend(editfield);

        // map esc and enter to blur event
        editfield.keyup(function(e){
            if(e.key == "Escape" || e.key == 'Enter'){
                editfield.blur();
            }
        });


        editfield.on('blur focusout', function(){
            var newname = $(this).val();

            // check if instancename hasn't changed do nothing
            if(instancename == newname){
                div.prepend(editingbutton);
                div.prepend(link);
                editfield.remove();
                return;
            }

            // build the url
            var url = M.cfg.wwwroot+'/lib/ajax/service.php?sesskey='+M.cfg.sesskey+'&info=core_update_inplace_editable';

            // extract itemid
            var itemid = $(this).parents('li.activity:first').attr('id');
            itemid = itemid.replace('module-', '');

            // ajax call to change the name
            var data = [
                {
                    index: 0,
                    args: {
                        component: 'core_course',
                        itemid: itemid,
                        itemtype: 'activityname',
                        value: newname
                    },
                    methodname: 'core_update_inplace_editable',
                }
            ];

            editfield.attr('disabled', 'disabled');

            $.ajax(url,{
                method: 'POST',
                data: JSON.stringify(data),
                success: function(data){
                    var newname = data[0].data.value;

                    link.find('.instancename').text(newname);
                    div.prepend(editingbutton);
                    div.prepend(link);
                    editfield.remove();
                },
                error: function(data){
                    var errorspan = $('<span>').html('<i class="fas fa-exclamation-triangle"></i>Modification échouée');
                    errorspan.css('color', '#F55');
                    errorspan.insertAfter(editfield);
                }
            });


        });

        editfield.select();
        link.detach();
        editingbutton.remove();
    }

    var init = function(){
        $('.header-editing').on('click', 'img.editbutton', function(){
            oneditbutton($(this));
        });

        $('.modtype_resource').on('click', 'img.editbutton', function(){
            oneditbutton($(this));
        });
    }

    return {
        init: init
    }
});