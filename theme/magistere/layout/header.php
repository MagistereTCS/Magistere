<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

global $CFG;

$hasheading = ($PAGE->heading);

$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hasheader = (empty($PAGE->layout_options['noheader']));

$hassidepre = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-pre', $OUTPUT));
$hassidepost = (empty($PAGE->layout_options['noblocks']) && $PAGE->blocks->region_has_content('side-post', $OUTPUT));

$showsidepre = ($hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));

$isfrontpage = $PAGE->bodyid == "page-site-index";

$iscoursepage = strpos($PAGE->pagetype, 'course-view') === 0 || ($PAGE->pagelayout == "incourse" && $PAGE->course->format != "flexpage");

$hasshortname = (!empty($PAGE->theme->settings->shortname));
$hasgeneralalert = (!empty($PAGE->theme->settings->generalalert));

$custommenu = $OUTPUT->custom_menu();

$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

//03/05/2019-TCS-JBL-3067 BEGIN
$haseditingrole = false;
if ($iscoursepage) {
    $course_context = context_course::instance($PAGE->course->id);
    $haseditingrole = (has_capability('moodle/site:manageblocks', $course_context) && has_capability('moodle/course:update', $course_context));
}
//03/05/2019-TCS-JBL-3067 END

// 05/12/2017-TCS-NNE-1998 START
$usermenu = $OUTPUT->user_menu();
$display_reset_aca = (!isset($USER->profile['codaca']) || (isset($USER->profile['codaca']) && (intval($USER->profile['codaca']) == 0 || $USER->profile['codaca'] == '')));
// 05/12/2017-TCS-NNE-1998 END
?>

<header role="banner" class="navbar  moodle-has-zindex">
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
            <?php echo $OUTPUT->navbar_home(); ?>
            <?php echo $OUTPUT->navbar_button(); ?>
            <?php echo $OUTPUT->user_menu(); ?>
            <?php echo $OUTPUT->navbar_plugin_output(); ?>
            <?php echo $OUTPUT->search_box(); ?>
            <div class="nav-collapse collapse">
                <?php echo $OUTPUT->custom_menu(); ?>
                <ul class="nav pull-right">
                    <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="container-fluid clearfix">
<?php
    //03/05/2019-TCS-JBL-3067 BEGIN
    if($haseditingrole){
        echo $OUTPUT->navbar();
    }
?>
<nav class="breadcrumb-button"><?php echo $PAGE->button; ?></nav>
<?php
    //03/05/2019-TCS-JBL-3067 END

if ($iscoursepage && $PAGE->course->id != 1) { ?>

    <div id="courseheader">
        <?php echo $this->get_icon_collection_course_id($PAGE->course->id) ?>
        <h1><?php echo $PAGE->heading . " " . $this->display_favorite_course_button($PAGE->course->id)?></h1>
    </div><?php
}
if (($isfrontpage) && ($hasgeneralalert)) { ?>
    <div id="page-header-generalalert"><?php
        echo $PAGE->theme->settings->generalalert; ?>
    </div><?php
} ?>

</div>
<div id="page" class="container-fluid clearfix">

<?php if ($display_reset_aca){  ?>
    <div id="reset_mainaca" title="R&eacute;initialisation de ma plateforme de rattachement" style="display:none">

        <div id="reset_mainaca_content" class="felement fsubmit" style="margin: -0.5em -1em; margin-top: 10px;">
            <p>Le choix de votre plateforme de rattachement va &ecirc;tre r&eacute;initialis&eacute;.</p>
            <p>Vous aurez la possibilit&eacute; de s&eacute;lectionner une nouvelle plateforme de rattachement lors de votre prochaine connexion.</p>
            <div style='width:100%;text-align:right;margin-top: 30px;'>
                <input name="validate" value="Valider et continuer" type="button" id="bt_validate" style='margin-right:5px'>
                <input name="cancel" value="Annuler" type="button" id="bt_cancel">
            </div>
        </div>
        <div id="reset_mainaca_conf" class="felement fsubmit" style="display:none;text-align:center">Chargement...</div>
    </div>
<?php 

$url = $CFG->wwwroot.'/?raca=o';
$PAGE->requires->js_call_amd("theme_magistere/layout_header", "init", array($url));

}

if($PAGE->user_is_editing()){
    $PAGE->requires->js_call_amd('theme_magistere/editing_action', 'init');
}

?>
