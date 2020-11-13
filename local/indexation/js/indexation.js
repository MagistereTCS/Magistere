$(function() {
    var options = {
        "monthNames": ['janvier', 'f&eacute;vrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'ao&ucirc;t', 'septembre', 'octobre', 'novembre', 'd&eacute;cembre'],
        "monthNamesShort": ['janv.', 'f&eacute;vr.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'ao&ucirc;t', 'sept.', 'oct.', 'nov.', 'd&eacute;c.'],
        "dayNames": ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
        "dayNamesShort": ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'],
        "dayNamesMin": ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
        "dateFormat": 'dd/mm/yy',
        "weekHeader": 'Sem.',
        "firstDay": 1,
        "currentText": 'Aujourd\'hui',
        "closeText": 'Fermer',
        "prevText": 'Pr&eacute;c&eacute;dent',
        "nextText": 'Suivant',
        "isRTL": false,
        "showMonthAfterYear": false
    };

    $('#id_startdate').datepicker(options);
    $('#id_enddate').datepicker(options);

    var academie_select = $('#id_academie');
    var origin_select = $('#id_origin');
    var departement_select = $('#id_departement');

    var academie_fitem = $('#fitem_id_academie');
    var departement_fitem = $('#fitem_id_departement');
    var espe_fitem = $('#fitem_id_espe');

    // code aca or code origin currently selected
    var selected_code = '';

    origin_select.on('change', function() {
        var selected_val = origin_select.find('option:selected').val();
        init_selects(selected_val);

        if (selected_val == 'academie') {
            $('#id_code_pres').val("");
        } else {
            selected_code = code_origin[selected_val].code;
            $('#id_code_pres').val(code_origin[selected_val].code);
            $('input[name="code"]').val(code_origin[selected_val].id);
        }

        update_course_identification();
    });

    academie_select.on('change', function() {
        var option = academie_select.find('option:selected');
        update_departement_select(option.val());
        departement_select.val(0);

        var code = option.val();
        code = code_origin[aca_uri[code]];
        selected_code = code.code;
        $('#id_code_pres').val(code.code);
        $('input[name="code"]').val(code.id);

        update_course_identification();
    });

    var update_departement_select = function(codeaca, keep_selected) {
        departement_select.find('option').each(function() {
            var val = parseInt($(this).val());
            if (val == 0) {
                return;
            }

            if (($(this).prop('selected') && keep_selected) || (departements[codeaca] !== undefined && departements[codeaca].indexOf(val) > -1)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    };

    var init_selects = function(selected_val, keep_selected) {
        academie_fitem.hide();
        departement_fitem.hide();
        espe_fitem.hide();

        if (selected_val == 'academie') {
            var aca = academie_fitem.find('option:selected').val();
            update_departement_select(aca, keep_selected);
            academie_fitem.show();
            departement_fitem.show();
        } else if (selected_val == 'espe') {
            espe_fitem.show();
        }
    };


    function split(val) {
        return val.split(/,\s*/);
    }

    function extractLast(term) {
        return split(term).pop();
    }

    $('[name="keywords"]').autocomplete({
        source: function(request, response) {
            $.support.cors = true;
            console.log(request.term);
            $.ajax({
                url: getkeywordsurl,
                crossDomain: true,
                dataType: 'json',
                data: {
                    textparam: extractLast(request.term)
                },
                success: function(s) {
                    response(s);
                },
                error: function(s) {
                    console.dir(s);
                }
            });
        },
        search: function() {
            var term = extractLast(this.value);
            if (term.length < 2) {
                return false;
            }
        },
        focus: function() {
            return false;
        },
        select: function(event, ui) {
            var terms = split(this.value);
            terms.pop();
            terms.push(ui.item.value);
            terms.push('');
            this.value = terms.join(', ');

            return false;
        }
    });

    // Code that manage to save the version note on the client side
    var version_note = {};
    var previous_version;
    var current_version = $('input[name="currentversion"]').val();
    var hiddenfieldprevious = $('input[name="previousnote"]');
    
    $('select[name="version"]').on('focus', function() {
        previous_version = this.value;
    }).change(function() {
        var select = $(this);

        var version = select.find('option:selected').val();

        var textareanote = $('textarea[name="currentnote"');
        // Save previous note
        version_note[previous_version] = textareanote.val();

        // save the content of the initial current version
        if(current_version){
            hiddenfieldprevious.val(version_note[current_version]);
        }
        // Load current version note
        if (!version_note[version]) {
            version_note[version] = '';
        }

        textareanote.val(version_note[version]);

        previous_version = this.value;
    });

    var resize_matricule = function(){
        var matricule = $('#id_matricule');
        var display = $('<p>').text(matricule.val());

        display.css('font-size', matricule.css('font-size'));
        display.css('font-family', matricule.css('font-family'));
        display.css('display', 'inline');

        var width = display.appendTo('body').width();
        display.remove();

        $('#id_matricule').width(width);
    }

    // code to compute the course version dynamically when the user make a change
    var update_course_identification = function(){
        var year = $('input[name="year"]').val();
        var version = $('select[name="version"] option:selected').val();
        var intitule = $('input[name="intitule"]').val();

        var matricule = year+'_'+selected_code+'_'+intitule+'_'+version;

        $('input[name="matricule"]').val(matricule);
        
        resize_matricule();
    }



    $('select[name="version"], input[name="year"], input[name="intitule"]').change(function(){
        update_course_identification();
    })

    // INIT PART
    resize_matricule();

    var selected_val = origin_select.find('option:selected').val();
    init_selects(selected_val, true);

    $('.tabs .tab').click(function() {
        var cls = $(this).attr('class');
        cls = cls.split(' ');

        $('.tabs .tab').removeClass('selected');
        $(this).addClass('selected');
        $('.panel').hide();

        $('.panel.' + cls[1]).show();
    });

    // INIT TAB ACCORDING HASH IN LOCATION
    var anchor = window.location.hash;
    if (anchor == '#detail') {
        $('.tab.detail').addClass('selected');
        $('.panel').hide();
        $('.panel.detail').show();
    } else if (anchor == '#organisme') {
        $('.tab.organisme').addClass('selected');
        $('.panel').hide();
        $('.panel.organisme').show();
    } else if (anchor == '#version') {
        $('.tab.version').addClass('selected');
        $('.panel').hide();
        $('.panel.version').show();
    } else {
        $('.tab.general').addClass('selected');
    }

    $('#fgroup_id_public_group').on('click', 'input[type="checkbox"]', function() {
        var formateurLabel = 'Formateurs';

        if ($(this).next('label').text() == formateurLabel) {
            if ($(this).prop('checked')) {
                $('#fgroup_id_public_group input[type="checkbox"][id!="' + $(this).attr('id') + '"]').prop('checked', false);
                return;
            }

            return;
        }

        // Otherwise, uncheck 'Formateur'
        $('#fgroup_id_public_group label').each(function() {
            if ($(this).text() == formateurLabel) {
                $(this).prev().prop('checked', false);
                return false; // Stop loop
            }
        });

    });

    // when the user press enter in a text field, do nothing (otherwise it will send the form to the bad adress)
    $('input[type="text"').keypress(function (e) {
        if (e.which != 13) {
            return;
        }

        e.preventDefault();
    });


    // INIT CODE ORIGIN
    var origin  = $('select[name="origin"] option:selected').val();
    var code = '';

    if(origin != "academie"){
        code = code_origin[origin].code;
    }else{
        var aca = $('select[name="academie"] option:selected').val();

        code = code_origin[aca_uri[aca]].code;
    }

    selected_code = code;

    // Fields controls: year, title and version
    $('#id_year').attr('maxlength', '2');
    $('#id_intitule').attr('maxlength', '15');
    $('#id_version').attr('maxlength', '3');
});