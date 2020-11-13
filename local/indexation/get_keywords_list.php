<?php

/**
 * Fichier appel ajax qui retourne un objet Json contenant une liste d'objet keywords
 *
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

$needle = required_param('textparam', PARAM_TEXT);

if(($hubDB = databaseConnection::instance()->get('hub')) === false){
    return;
}

$matchedkeywords = $hubDB->get_records_sql('
SELECT 
DISTINCT lik.keyword
FROM {local_indexation_keywords} lik
WHERE lik.keyword LIKE "%'.$needle.'%"
ORDER BY lik.keyword');

$matchedkeywords = array_keys($matchedkeywords);

echo json_encode($matchedkeywords);