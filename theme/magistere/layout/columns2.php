<?php
/**
 * Aardvark theme for Moodle - Material-inspired theme based on bootstrap.
 *
 * DO NOT MODIFY THIS THEME!
 * COPY IT FIRST, THEN RENAME THE COPY AND MODIFY IT INSTEAD.
 *
 * For full information about creating Moodle themes, see:
 * http://docs.moodle.org/dev/Themes_2.0
 *
 * The two column layout.
 *
 * @package   theme_aardvark
 * @author    Shaun Daubney
 * @copyright 2017 Newbury College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

// Get the HTML for the settings bits.
$html = theme_aardvark_get_html_for_settings($OUTPUT, $PAGE);

$regionmain = 'span9 pull-right';
$sidepre = 'span3 desktop-first-column';

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<?php require('head.php'); ?>

<body <?php echo $OUTPUT->body_attributes('two-column'); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<?php require('header.php'); ?>



<div id="page-content" class="row-fluid">
    <section id="region-main" class="<?php echo $regionmain; ?>">
        <?php
        echo $OUTPUT->course_content_header();
        echo $OUTPUT->main_content();
        echo $OUTPUT->course_content_footer();
        ?>
    </section>
    <?php echo $OUTPUT->blocks('side-pre', $sidepre);
    ?>
</div>


<footer id="page-footer">
    <?php require('footer.php'); ?>
</footer>

<?php echo $OUTPUT->standard_end_of_body_html() ?>


</body>
</html>
