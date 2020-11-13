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
 * @package   theme_aardvark
 * @author    Shaun Daubney
 * @copyright 2017 Newbury College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$THEME->name = 'magistere';
$THEME->doctype = 'html5';
$THEME->parents = array('aardvark','bootstrapbase','clean');
$THEME->sheets = array();
$THEME->yuicssmodules = array();
$THEME->enable_dock = false;
$THEME->editor_sheets = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_magistere_process_css';
$THEME->requiredblocks = 'settings';

$THEME->scss = 'main';

$THEME->layouts = array(
    'format_flexpage' => array(
        'file' => 'flexpage.php',
        'regions' => array('side-pre', 'main', 'side-post'),
        'defaultregion' => 'main',
        'options' => array('langmenu'=>true),
    ),
    // Most backwards compatible layout without the blocks - this is the layout used by default.
    'base' => array(
        'file' => 'columns1.php',
        'regions' => array(),
    ),
    // Standard layout with blocks, this is recommended for most pages with general information.
    'standard' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    // Main course page.
    'course' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu'=>true),
    ),
    'coursecategory' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    // part of course, typical for modules - default page layout if $cm specified in require_login().
    'incourse' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    // The site home page.
    'frontpage' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
        'options' => array('nonavbar'=>true),
    ),
    // Server administration scripts.
    'admin' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // My dashboard page.
    'mydashboard' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu'=>true),
    ),
    // My public page.
    'mypublic' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    'login' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('langmenu'=>true),
    ),

    // Pages that appear in pop-up windows - no navigation, no blocks, no header.
    'popup' => array(
        'file' => 'popup.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>true),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nocoursefooter'=>true),
    ),
    // Embeded pages, like iframe/object embeded in moodleform - it needs as much space as possible.
    'embedded' => array(
        'file' => 'embedded.php',
        'regions' => array()
    ),
    // Used during upgrade and install, and for the 'This site is undergoing maintenance' message.
    // This must not have any blocks, links, or API calls that would lead to database or cache interaction.
    // Please be extremely careful if you are modifying this layout.
    'maintenance' => array(
        'file' => 'maintenance.php',
        'regions' => array(),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>false),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => 'embedded.php',
        'regions' => array(),
    ),
    // The pagelayout used for reports.
    'report' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // The pagelayout used for safebrowser and securewindow.
    'secure' => array(
        'file' => 'secure.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre'
    ),
    // My dashboard page.
    'mydashboard_pages' => array(
        'file' => 'columns2post.php',
        'regions' => array('side-post'),
        'defaultregion' => 'side-post',
        'options' => array('langmenu'=>true),
    ),
    //
    'frontal_notconnected' => array(
        'file' => 'columns1.php',
        'regions' => array(),
        'options' => array('langmenu'=>false, 'nonavbar'=>true),
    ),
    // Bloc Barre de progression
    'block_progress_overview' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre', 'main'),
        'defaultregion' => 'main',
    ),
    'block_configurable_reports' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    'preonly' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // Should display the content and basic headers only.
    'magistere_offers' => array(
        'file' => 'offers.php',
        'regions' => array('side-pre', 'main'),
        'defaultregion' => 'main',
        'options' => array('nonavbar'=>true, 'noblocks' =>true),
    ),
    'format_modular' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'main', 'side-post'),
        'defaultregion' => 'main'
    ),
    'workflow' => array(
        'file' => 'columns2.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'indexation' => array(
        'file' => 'columns3.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu'=>true),
    ),
    // My index page.
    'myindex' => array(
        'file' => 'columns2post.php',
        'regions' => array('side-post'),
        'defaultregion' => 'side-post',
    ),
);

/** List of javascript files that need to be included on each page */
$THEME->javascripts = array('custom_js.min');
$THEME->javascripts_footer = array();