/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($) {


    return {
        init: function(api_url,dispositif_text,module_text,session_text){

            var options = {
                    "monthNames": ['janvier', 'f&eacute;vrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'ao&ucirc;t', 'septembre', 'octobre', 'novembre', 'd&eacute;cembre'] ,
                    "monthNamesShort": ['janv.', 'f&eacute;vr.', 'mars', 'avril', 'mai', 'juin', 'juil.', 'ao&ucirc;t', 'sept.', 'oct.', 'nov.', 'd&eacute;c.'] ,
                    "dayNames": ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'] ,
                    "dayNamesShort": ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'] ,
                    "dayNamesMin": ['D','L','M','M','J','V','S'] ,
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


            $('#startdate').datepicker(options);
            $('#enddate').datepicker(options);


            $("#show_participants").on("click",function() {
                if ($(this).hasClass("show")) {
                    $("#participants li").show();
                    $("#show_participants").html("<i class='fas fa-chevron-up'></i> Voir moins");
                    $(this).removeClass("show");
                }else{
                    $("#participants li:gt(2)").hide();
                    $("#show_participants").html("<i class='fas fa-chevron-down'></i> Voir tous");
                    $(this).addClass("show");
                }
            });

            $("#formateurs").on("mousedown", "option", function(e) {
                e.preventDefault();
                $(this).prop("selected", $(this).prop("selected") ? false : true);
                return false;
            });

            $("#add_all_formateurs").change(function() {
              if($("#add_all_formateurs").prop("checked"))
              {
                $("#formateurs option").prop("selected", true);
                $("#formateurs select").prop("disabled", true);
              }else{
                $("#formateurs option").prop("selected", false);
                $("#formateurs select").prop("disabled", false);
              }
            });

            $("#group_select").change(function(){
              if ($(this).children("option:selected").val() == "newgroup")
              {
                $("#new_group").show();
              }else{
                $("#new_group").hide();
              }
            });

            $("#selnone").click(function(e) {
              e.preventDefault();
              $("#databases option").prop("selected", false);
            });


            $("#dispositif_search_btn").on( "click", function() {
              loadDispositifs();
            });

            $("#dispositif_reset_btn").on( "click", function() {
              dispositif_unlock();
              module_disable();
              session_disable();
            });

            $("#dispositif_res").on( "click", "li", function() {
              if ($(this)[0].hasAttribute("data-id")) {
                dispositif_lock($(this).attr("data-id"),$(this).attr("data-name"));
              }
            });

            $("#dispositif_input").keypress(function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if(keycode == "13"){
                   loadDispositifs();
                }
            });

            function dispositif_lock(id,name) {
              $("#dispositif_id").val(id);
              $("#dispositif_input").val(id+" : "+name);
              $("#dispositif_input").prop("disabled", true);
              $("#dispositif_search_btn").prop("disabled", true);
              $("#dispositif_res").html("");
              module_enable();
              show_module();
            }

            function dispositif_unlock() {
              $("#dispositif_res").html("");
              $("#dispositif_input").val("");
              $("#dispositif_input").prop("disabled", false);
              $("#dispositif_search_btn").prop("disabled", false);
              module_disable(false);
              session_disable();
              show_module(false);
            }

            var dispositifTimeout = null;
            $("#dispositif_input").on("keyup", function() {
                clearTimeout(dispositifTimeout);
                dispositifTimeout = setTimeout(function() {
                    loadDispositifs();
                }, 500);
            });

            function loadDispositifs(){
                clearTimeout(dispositifTimeout);
                if ($("#dispositif_input").val().length > 0 && $("#dispositif_input").val().length < 3) {
                    return false;
                }
                $.ajax({
                    type: "POST",
                    url: api_url,
                    data:JSON.stringify({"type":"dispositifs", "courseid":$("#course_id").val(), "startdate":dateToTimestamp($("#startdate").val()), "enddate":dateToTimestamp($("#enddate").val()), "dispositif":$("#dispositif_input").val()}),
                    datatype: "json",
                    success: function (response) {
                        var dispositifs = JSON.parse(response).dispositifs;
                        var ul = $("<ul>");
                        if (dispositifs.length > 0)
                        {
                            for (var i = 0, l = dispositifs.length; i < l; ++i) {
                                var modules = " " + dispositifs[i].modulecount + " module";
                                if(dispositifs[i].modulecount>1){modules+="s";}
                                ul.append('<li data-id="'+dispositifs[i].dispositif_id+'" data-name="'+dispositifs[i].dispositif_name+'"><div class ="title">' + dispositifs[i].dispositif_id_high + ' : ' + dispositifs[i].dispositif_name_high+' ('+dispositifs[i].dispositif_origin+') </div><div class ="module">'+modules+'</div></li>');
                            }
                        }else{
                            ul.append('<li>Aucun résultat</li>');
                        }
                        $("#dispositif_res").html(ul);
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            }




            $("#module_search_btn").on( "click", function() {
              loadModules();
            });

            $("#module_reset_btn").on( "click", function() {
              module_unlock();
              session_disable();
            });

            $("#module_res").on( "click", "li", function() {
              if ($(this)[0].hasAttribute("data-id")) {
                module_lock($(this).attr("data-id"),$(this).attr("data-name"));
              }
            });

            $("#module_input").keypress(function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if(keycode == "13"){
                   loadModules();
                }
            });

            function module_lock(id,name) {
              $("#module_id").val(id);
              $("#module_input").val(id+" : "+name);
              $("#module_input").prop("disabled", true);
              $("#module_search_btn").prop("disabled", true);
              $("#module_res").html("");
              session_enable();
              show_session();
            }

            function module_unlock() {
              $("#module_res").html("");
              $("#module_input").val("");
              $("#module_input").prop("disabled", false);
              $("#module_search_btn").prop("disabled", false);
              session_lock("","");
              show_session(false);
            }

            function module_disable() {
              $("#module_id").val("");
              $("#module_input").val("");
              $("#module_input").prop("disabled", true);
              $("#module_search_btn").prop("disabled", true);
              $("#module_reset_btn").prop("disabled", true);
              $("#module_res").html("");
              session_disable();
            }

            function module_enable() {
              $("#module_id").val("");
              $("#module_input").val("");
              $("#module_input").prop("disabled", false);
              $("#module_search_btn").prop("disabled", false);
              $("#module_reset_btn").prop("disabled", false);
              $("#module_res").html("");
            }

            var moduleTimeout = null;
            $("#module_input").on("keyup", function() {
                clearTimeout(moduleTimeout);
                moduleTimeout = setTimeout(function() {
                    loadModules();
                }, 500);
            });
            function loadModules(){
                clearTimeout(moduleTimeout);
                if ($("#module_input").val().length > 0 && $("#module_input").val().length < 3) {
                    return false;
                }
                $.ajax({
                    type: "POST",
                    url: api_url,
                    data:JSON.stringify({"type":"modules", "courseid":$("#course_id").val(), "startdate":dateToTimestamp($("#startdate").val()), "enddate":dateToTimestamp($("#enddate").val()), "dispositif":$("#dispositif_id").val(), "module":$("#module_input").val()}),
                    datatype: "json",
                    success: function (response) {
                        var modules = JSON.parse(response).modules;
                        var ul = $("<ul>");
                        if (modules.length > 0)
                        {
                            for (var i = 0, l = modules.length; i < l; ++i) {
                                var sessions = " " + modules[i].sessioncount + " session";
                                if(modules[i].sessioncount>1){sessions+="s";}
                                ul.append('<li data-id="'+modules[i].module_id+'" data-name="'+modules[i].module_name+'"><div class ="title">Module ' + modules[i].module_id_high + ' : ' + modules[i].module_name_high+'</div><div class="session">'+sessions+'</div></li>');
                            }
                        }else{
                            ul.append('<li>Aucun résultat</li>');
                        }
                        $("#module_res").html(ul);
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            }


            $("#session_search_btn").on( "click", function() {
              loadSessions();
            });

            $("#session_reset_btn").on( "click", function() {
              session_unlock();
            });

            $("#session_res").on( "click", "li", function() {
              if ($(this)[0].hasAttribute("data-id")) {
                session_lock($(this).attr("data-id"),$(this).attr("data-name"));
              }
            });

            $("#session_input").keypress(function(event) {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if(keycode == "13"){
                   loadSessions();
                }
            });

            function session_lock(id,name) {
              $("#session_id").val(id);
              $("#session_input").val(id+" : "+name);
              $("#session_input").prop("disabled", true);
              $("#session_search_btn").prop("disabled", true);
              $("#session_res").html("");
              loadSession();
            }

            function session_unlock() {
              $("#session_res").html("");
              $("#session_input").val("");
              $("#session_input").prop("disabled", false);
              $("#session_search_btn").prop("disabled", false);
              show_sessiondata(false);
            }

            function session_disable() {
              $("#session_id").val("");
              $("#session_input").val("");
              $("#session_input").prop("disabled", true);
              $("#session_search_btn").prop("disabled", true);
              $("#session_reset_btn").prop("disabled", true);
              $("#session_res").html("");
            }

            function session_enable() {
              $("#session_id").val("");
              $("#session_input").val("");
              $("#session_input").prop("disabled", false);
              $("#session_search_btn").prop("disabled", false);
              $("#session_reset_btn").prop("disabled", false);
              $("#session_res").html("");
            }

            var sessionTimeout = null;
            $("#session_input").on("keyup", function() {
                clearTimeout(sessionTimeout);
                sessionTimeout = setTimeout(function() {
                    loadSessions();
                }, 500);
            });

            function loadSessions(){
                clearTimeout(sessionTimeout);
                if ($("#session_input").val().length > 0 && $("#session_input").val().length < 3) {
                    return false;
                }
                $.ajax({
                    type: "POST",
                    url: api_url,
                    data:JSON.stringify({"type":"sessions", "courseid":$("#course_id").val(), "startdate":dateToTimestamp($("#startdate").val()), "enddate":dateToTimestamp($("#enddate").val()), "dispositif":$("#dispositif_id").val(), "module":$("#module_id").val(), "session":$("#session_input").val()}),
                    datatype: "json",
                    success: function (response) {
                        var sessions = JSON.parse(response).sessions;
                        var ul = $("<ul>");
                        if (sessions.length > 0)
                        {
                            for (var i = 0, l = sessions.length; i < l; ++i) {
                                var users = sessions[i].formateurs + " formateurs / " + sessions[i].participants + " participants";
                                ul.append('<li ' + (sessions[i].linked?'':'data-id="'+sessions[i].session_id+'" data-name="'+sessions[i].formation_place+'"') + '><div class="session_logo">' + (sessions[i].linked?"<a href='" + sessions[i].linkedurl + "' target='_blank'><i class='fas fa-link fa-3x'></i></a>":"") + '</div><div class ="session_infos"><div class ="session_date">du '+sessions[i].startdate+' au '+sessions[i].enddate+' </div><div class ="session_attendance">' + users + '</div><div style="clear: both;"></div><div class="session_title">' + sessions[i].formation_place_high + '</div></div></li>');
                            }
                        }else{
                            ul.append('<li>Aucun résultat</li>');
                        }
                        $("#session_res").html(ul);
                        show_session();
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            }

            function loadSession(){
                $.ajax({
                    type: "POST",
                    url: api_url,
                    data:JSON.stringify({"type":"session", "courseid":$("#course_id").val(), "dispositif":$("#dispositif_id").val(), "module":$("#module_id").val(), "session":$("#session_id").val()}),
                    datatype: "json",
                    success: function (response) {
                        var session = JSON.parse(response).session;

                        // Load participants
                        loadParticipants(session.participants);
                        // Load formateurs
                        loadFormateurs(session.formateurs);
                        // Load groups
                        loadGroups(session.groups);
                        // Load groupings
                        loadGroupings(session.groupings);
                        
                        show_sessiondata();
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            }

            function loadParticipants(participants) {
                $("#participants ul").html("");
                $("#participants span").html("PARTICIPANTS ("+participants.length+")");
                for (var i = 0, l = participants.length; i < l; ++i) {
                    $("#participants ul").append("<li"+(i>2?" style='display:none'":"")+">" + participants[i].firstname + " " + participants[i].lastname + "</li>");
                }
                if (participants.length > 3) {
                    $("#show_participants").show();
                }
            }

            function loadFormateurs(formateurs) {
                $("#formateurs select").find("option").remove();
                $("#formateurs select").find("optgroup").remove();
                $("#formateurs select").append("<optgroup label='FORMATEURS ("+formateurs.length+")'>");
                for (var i = 0, l = formateurs.length; i < l; ++i) {
                    $("#formateurs select optgroup").append("<option value='"+formateurs[i].id+"'>" + formateurs[i].firstname + " " + formateurs[i].lastname + "</options>");
                }
            }

            function loadGroups(groups) {
                $("#groups select").find("option").remove();
                $("#groups select").append("<option value='nogroup' style='font-weight:bold'>Aucun groupe</options>");
                for (var i = 0, l = groups.length; i < l; ++i) {
                    $("#groups select").append("<option value='"+groups[i].id+"'>" + groups[i].name + "</options>");
                }
                $("#groups select").append("<option value='newgroup' style='font-weight:bold'>+ Créer un nouveau groupe</options>");
                $("#groups select").change();
            }

            function loadGroupings(groupings) {
                $("#groupings select").find("option").remove();
                $("#groupings select").append("<option value='newgrouping'>Automatique</options>");
                for (var i = 0, l = groupings.length; i < l; ++i) {
                    $("#groupings select").append("<option value='"+groupings[i].id+"'>" + groupings[i].name + "</options>");
                }
            }

            function show_module(show) {
	            if (show == undefined){show=true;}
	            $("#module").toggle(show);
	            $("#module_label").toggle(show);
	            if (show == false){show_session(false);}
            }
            function show_session(show) {
	            if (show == undefined){show=true;}
	            $("#session").toggle(show);
	            $("#session_label").toggle(show);
	            if (show == false){show_sessiondata(false);}
            }
            function show_sessiondata(show) {
	            if (show == undefined){show=true;}
	            $("#session_data").toggle(show);
            }

            function load() {
                var dispositif = $("#dispositif_id").val(),
                    module = $("#module_id").val(),
                    session = $("#session_id").val();
                if (dispositif.length == 10 && module > 0 && session > 0) {
                      
                    $("#dispositif_input").val(dispositif);
                    dispositif_lock(dispositif,dispositif_text);
                
                  	$("#module_input").val(module);
                  	module_lock(module,module_text);
                    $("#session_input").val(session);
                    session_lock(session,session_text);
                }
            }

            function dateToTimestamp(date) {
                $dates = date.split('/');
                console.log($dates);
                return (new Date($dates[2],$dates[1]-1,$dates[0])).getTime()/1000;
            }

            function init() {
                load();
            }

            $(document).ready(function () {
            	init();
            });

        }
    };
});