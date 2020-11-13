<?php

/**
 * Default English language file
 *
 *
 * @package    local_custom_reports
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['messageprovider:send_notification'] = 'Custom Reports notifications';
$string['pluginname'] = "Custom reports";
$string['query_stats_2020'] = 'Nouvelle requête statistique 2020';
$string['allowed_cat'] = 'Seules les catégories suivantes sont prises en compte par cette requête statistique : Gabarit / Parcours de formation / Session de formation / Archive';
$string['notification_subject'] = '[STATS m@gistère] Résultat de votre requête statistique du {$a}';
$string['notification_message'] = '<p>Bonjour,</p>
<p>Vous trouverez en pièce jointe de ce mail un fichier contenant les résultats de la requête que vous avez lancé sur votre domaine m@gistère le {$a}.<br/>
Ceci est un message automatique. Merci de ne pas répondre à ce courriel.<br/>
</p>';
$string['notification_message_no_records'] = '<p>Bonjour,</p>
<p>La requête que vous avez lancé sur votre domaine m@gistère le {$a} n\'abouti à aucun résultat.<br/>
Ceci est un message automatique. Merci de ne pas répondre à ce courriel.<br/>
</p>';