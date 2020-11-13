/* jshint ignore:start */
define(['jquery', 'core/templates', 'media_videojs/video-lazy'], function($, templates, videojs) {
    function loadingCourses(query_url, course_offers_url) {
        var search = $('#search-input').val();
        var filter = $('#search-select').val();
        var haveArchive = 0 != $('#search-select option[value=archive]').length;
        
        $( window ).on( "orientationchange", function( event ) {
            if(screen.width < 960){
                if(window.orientation == 90){
                    $('.item.course-tile').addClass('list-group-item');
                    $('.grid').removeClass('selected-view');
                    $('.list').addClass('selected-view');
                } else {
                    $('.item.course-tile').removeClass('list-group-item');
                    $('.list').removeClass('selected-view');
                    $('.grid').addClass('selected-view');
                    $('.item.course-tile').addClass('grid-group-item');
                }
            }
        });

        if(filter == "archive" || filter == "favoris" || !haveArchive){
            $('#btn_showArchivedCourses').hide();
        } else {
            $('#btn_showArchivedCourses').show();
        }

        $('#btn_showArchivedCourses').on('click', function(){
            $('#search-select').val("archive");
            loadingCourses(query_url, course_offers_url);
        });

        getCourses(query_url, 'main', search, filter, course_offers_url);
    }

    function loadingTemplate(renderName, obj, query_url, parent){
        var view = $('.user-view-pref').attr('v');
        templates.render(renderName, obj)
            .then(function (html, js) {
                parent.html(html);
                changeView(view);
                $('<script>').append(js).appendTo('body');
                parent.fadeIn(100, function() {
                    // Animation complete
                    chargeProgressValueToSVG();
                    loadingModal(query_url);
                    manageFavoriteCourse(query_url);
                    manageModView(query_url);
                });
            });
    }

    function getCourses(query_url, module, search, filter, course_offers_url) {
        var parent = $('.courses-content');
        $.ajax({
            type: 'POST',
            url: query_url,
            data: JSON.stringify({"module": module ,"search":search ,"filter":filter}),
            dataType: 'json',
            beforeSend: function (){
                $('.spinner').show();
            },
            success: function (response) {
                courses = response;
                if(!courses.error){
                    if(search.length != 0){
                        if(courses.courses.length > 0){
                            if(filter == "archive"){
                                parent.addClass('archived');
                            } else {
                                parent.removeClass('archived');
                            }
                            if(filter == "favoris"){
                                $('.archived-course-header').show();
                                collapseArchivedCourses(query_url, module, search, filter, course_offers_url);

                            } else {
                                cleanCollapseArchivedCourses();
                            }
                            loadingTemplate('local_myindex/courses', courses, query_url, parent);
                        } else {
                            if(filter == "favoris"){
                                parent.empty();
                                $('.archived-course-header').show();
                                getArchiveFavoriteCourses(query_url, module, search, filter, course_offers_url, courses.courses.length);
                                collapseArchivedCourses(query_url, module, search, filter, course_offers_url);
                            } else {
                                cleanCollapseArchivedCourses();
                                var obj = {"search":search,"course_offers_url":course_offers_url};

                                templates.render('local_myindex/no_course', obj)
                                    .then(function (html, js) {
                                        parent.html(html);
                                        $('<script>').append(js).appendTo('body');
                                        parent.fadeIn(400);
                                    });
                            }
                        }

                    } else {
                    	var haveArchive = 0 != $('#search-select option[value=archive]').length;
                        if(courses.courses.length == 0){
                            if(filter == "favoris"){
                                parent.empty();
                                getArchiveFavoriteCourses(query_url, module, search, filter, course_offers_url, courses.courses.length);
                                if (haveArchive) {
                                	$('.archived-course-header').show();
                                	collapseArchivedCourses(query_url, module, search, filter, course_offers_url);
                                }
                            } else {
                                cleanCollapseArchivedCourses();
                                var obj = {"no-course":true,"course_offers_url":course_offers_url};

                                templates.render('local_myindex/no_course', obj)
                                    .then(function (html, js) {
                                        parent.html(html);
                                        $('<script>').append(js).appendTo('body');
                                        parent.fadeIn(400);
                                    });
                            }
                        } else {
                            if(filter == "archive"){
                                parent.addClass('archived');
                            } else {
                                parent.removeClass('archived');
                            }
                            if(filter == "favoris"){
                            	if (haveArchive) {
                            		$('.archived-course-header').show();
                            		collapseArchivedCourses(query_url, module, search, filter, course_offers_url);
                            	}
                            } else {
                                cleanCollapseArchivedCourses();
                            }
                            loadingTemplate('local_myindex/courses', courses, query_url, parent);
                        }
                    }
                } else {
                    $('.archived-course-header').hide();
                    parent.html('Une erreur s\'est produite. Merci de rafraichir la page.');
                }

                // Code to hide spinner.
                $('.spinner').fadeOut(800);
            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    function getArchiveFavoriteCourses(query_url, module, search, filter, course_offers_url, courses_number){
        var parent = $('.myindex-archived-content');
        $.ajax({
            type: 'POST',
            url: query_url,
            data: JSON.stringify({"module": module ,"search":search ,"filter":filter,"archive":true}),
            dataType: 'json',
            success: function (response) {
                courses = response;
                if(!courses.error){
                    if(search.length != 0){
                        if(courses.courses.length > 0){
                            loadingTemplate('local_myindex/archived_courses', courses, query_url, parent);
                            $('.archived-course-header').show();
                        } else {
                            cleanCollapseArchivedCourses();
                            if(courses_number == 0){
                                parent = $('.courses-content');

                                var obj = {"search":search,"course_offers_url":course_offers_url};

                                templates.render('local_myindex/no_course', obj)
                                    .then(function (html, js) {
                                        parent.html(html);
                                        $('<script>').append(js).appendTo('body');
                                        parent.fadeIn(400);
                                    });
                            }
                        }

                    } else {
                        if(courses.courses.length == 0){
                            cleanCollapseArchivedCourses();
                            if(courses_number == 0){
                                parent = $('.courses-content');

                                var obj = {"no-course":true,"course_offers_url":course_offers_url};

                                templates.render('local_myindex/no_course', obj)
                                    .then(function (html, js) {
                                        parent.html(html);
                                        $('<script>').append(js).appendTo('body');
                                        parent.fadeIn(400);
                                    });
                            }
                        } else {
                            loadingTemplate('local_myindex/archived_courses', courses, query_url, parent);
                            $('.archived-course-header').show();
                        }
                    }
                } else {
                    cleanCollapseArchivedCourses();
                    parent.html('Une erreur s\'est produite sur les parcours favoris archivés. Merci de rafraichir la page.');
                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    }

    function collapseArchivedCourses(query_url, module, search, filter, course_offers_url, courses_number){
        var parent = $('.myindex-archived-content');

        $(".archived-courses-link").off("click");
        $(".archived-courses-link").on("click", function(e){
            $('#collapseArchivedCourses').find('.spinner').show();
            parent.empty();
            if(!$('#collapseArchivedCourses').hasClass('in')){
                $(this).removeClass('collapsed');
                $('#collapseArchivedCourses').addClass('in');
                getArchiveFavoriteCourses(query_url, module, search, filter, course_offers_url, courses_number);
            } else {
                $(this).addClass('collapsed');
                $('#collapseArchivedCourses').removeClass('in');
            }
            $('#collapseArchivedCourses').find('.spinner').fadeOut(800);
        });
    }

    function cleanCollapseArchivedCourses(){
        $('.archived-course-header').hide();
        $('.archived-courses-link').addClass('collapsed');
        $('#collapseArchivedCourses').removeClass('in');
        $('.myindex-archived-content').empty();
    }

    function chargeProgressValueToSVG(){
        $('.item.course-tile').each(function(){
            var val = parseInt($(this).find('.progress-user').attr('data-pct'));
            var circle = $(this).find('#svg #bar');

            if (isNaN(val)) {
                val = 0;
            }
            var r = circle.attr('r');
            var c = Math.PI*(r*2);

            if (val < 0) { val = 0;}
            if (val > 100) { val = 100;}

            var pct = ((100-val)/100)*c;

            circle.css({ strokeDashoffset: pct});
        });
    }

    function changeView(user_pref){
        if(user_pref == 'list'){
            $('.item.course-tile').addClass('list-group-item');
            $('.grid').removeClass('selected-view');
            $('.list').addClass('selected-view');
        } else {
            $('.item.course-tile').removeClass('list-group-item');
            $('.list').removeClass('selected-view');
            $('.grid').addClass('selected-view');
            $('.item.course-tile').addClass('grid-group-item');
        }
    }

    function validateForm(query_url, course_offers_url){
        // Gestion du champs de recherche dans le header
        $(".search .fa-search").off("click");
        $(".search .fa-search").on("click",function(){
            if($("#search-input").val().length > 2 || $("#search-input").val().length == 0){
                loadingCourses(query_url, course_offers_url);
            }
        });
        $("#search-input").bind("enterKey",function(){
            if($("#search-input").val().length > 2 || $("#search-input").val().length == 0){
                loadingCourses(query_url, course_offers_url);
            }
        });
        $("#search-input").keyup(function(e){
            if(e.keyCode === 13){
                $(this).trigger("enterKey");
            }
        });
        $('#search-select').on("change", function(){
            loadingCourses(query_url, course_offers_url);
        });
    }

    function manageFavoriteCourse(query_url){
        $('i.fav-icon').off("click");
        $('i.fav-icon').on("click",function(event){
            event.preventDefault();
            var favIcon = $(this);
            var ac = $(this).parents('.card-body').find('.show-detail a').attr('ref-modal-source');
            var id = $(this).parents('.card-body').find('.show-detail a').attr('ref-modal-id');
            var action = false;
            if(favIcon.hasClass('unfav')){
                action = true;
            }
            $.ajax({
                type:'POST',
                url: query_url,
                data:'{"module":"fav","ac":"'+ac+'","id":"'+id+'","action":'+action+'}',
                dataType:'json',
                success:function(response){
                    if(response.value == true){
                        favIcon.parents('.card-body').find('i.fav-icon').addClass('fav');
                        favIcon.parents('.card-body').find('i.fav-icon').removeClass('unfav');
                        favIcon.parents('.card-body').find('i.fav-icon').attr("title","Retirer de mes parcours favoris")
                        
                    } else {
                        favIcon.parents('.card-body').find('i.fav-icon').addClass('unfav');
                        favIcon.parents('.card-body').find('i.fav-icon').removeClass('fav');
                        favIcon.parents('.card-body').find('i.fav-icon').attr("title","Ajouter à mes parcours favoris")
                    }
                },
                error:function(error){
                    console.log(error);
                }
            });
        });
    }

    function manageModView(query_url){
        $('.change-view button').on("click",function(){
            if(!$(this).hasClass('selected-view')){
                var mod = 'grid';
                if($(this).hasClass('list')){
                    mod = 'list';
                }
                changeView(mod);

                $.ajax({
                    type:'POST',
                    url: query_url,
                    data:'{"module":"viewmod","mod":"'+mod+'"}',
                    dataType:'json',
                    success:function(response){
                        $('.user-view-pref').attr('v', response.value);
                    },
                    error:function(error){
                        console.log(error);
                    }
                });
            }
        });
    }

    function loadingModal(query_url){
        $('#region-main').off("click");
        $('#region-main').on("click", 'a[ref-bs-element="modal"]', function(){
            var id = $(this).attr('ref-modal-id');
            var source = $(this).attr('ref-modal-source');
            var idModal = '#' + $(this).attr('ref-modal');

            $.ajax({
                type:'POST',
                url: query_url,
                data:'{"module":"modal","ac":"'+source+'","id":'+id+'}',
                dataType:'json',
                success:function(response){
                    var obj = response;
                    var renderName = 'local_myindex/modal';

                    templates.render(renderName, obj)
                        .then(function(html, js) {

                            $('#myModal').html(html);

                            if(document.getElementsByClassName('video-js')[0]){
                                videojs(document.getElementsByClassName('video-js')[0], {}, function(){
                                    // Player (this) is initialized and ready.
                                });
                            }

                            $(idModal).modal('show');
                            $(idModal+' .modal').on('shown.bs.modal', function() {
                                $('body').addClass('modal-open');
                            });
                            $(idModal+' .modal').on('hidden.bs.modal', function() {
                                history.pushState("", document.title, window.location.pathname + window.location.search);
                                $('body').removeClass('modal-open');
                            });

                            $('<script>').append(js).appendTo('body');
                        });
                },
                error:function(error){
                    console.log(error);
                }
            });

        });
    }

    return {
        init : function(query_url, course_offers_url){
            loadingCourses(query_url, course_offers_url);
        },

        validateform : function(query_url, course_offers_url){
            validateForm(query_url, course_offers_url);
        }
    };
});