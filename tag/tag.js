
/**
 * Animated accordion
 **/
$(document).ready(function()
{
    // on récupère les span vides qui vont contenir les arrows
    var arrowCourse = $('#tagarea-core-course h3 span');
    var arrowCourseModules = $('#tagarea-core-course_modules h3 span');

    // initialisation au chargement de la page
    if ($('[name=core_course]').length) {
        $('#tagarea-core-course div.taggeditems').slideDown('fast');
        $('#tagarea-core-course div.controls-bottom').slideDown('fast');
        arrowUser.append('<i class="fas fa-caret-right"></i>');
        arrowCourse.append('<i class="fas fa-caret-down"></i>');
        arrowCourseModules.append('<i class="fas fa-caret-right"></i>');
    } else {
        $('#tagarea-core-course_modules div.taggeditems').slideDown('fast');
        $('#tagarea-core-course_modules div.controls-bottom').slideDown('fast');
        arrowUser.append('<i class="fas fa-caret-right"></i>');
        arrowCourse.append('<i class="fas fa-caret-right"></i>');
        arrowCourseModules.append('<i class="fas fa-caret-down"></i>');
    }

});
