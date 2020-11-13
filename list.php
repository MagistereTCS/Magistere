<?php
session_start();


$domains = array(
//    'ac-aix-marseille',
//'ac-amiens',
//'ac-besancon',
//'ac-bordeaux',
//'ac-caen',
//'ac-clermont',
//'ac-corse',
//'ac-creteil',
//'ac-dijon',
//    'ac-grenoble',
//'ac-guadeloupe',
//'ac-guyane',
//'ac-lille',
//'ac-limoges',
//'ac-lyon',
//'ac-martinique',
//'ac-mayotte',
//'ac-montpellier',
//'ac-nancy-metz',
//'ac-nantes',
//'ac-nice',
//'ac-noumea',
//'ac-orleans-tours',
//'ac-paris',
//'ac-poitiers',
//'ac-polynesie',
//'ac-reims',
//'ac-rennes',
//'ac-reunion',
//'ac-rouen',
//'ac-st-pierre-miquelon',
//'ac-strasbourg',
//'ac-toulouse',
'ac-versailles',
//'ac-wallis-futuna',
//    'reseau-canope',
'dgesco',
//    'dgrh',
//    'dne-foad',
//    'efe',
//    'ih2ef',
'frontal',
//'magistere-recette'
);


$hostname = $_SERVER['SERVER_NAME'];

$end = '';

if (isset($_POST['end']))
{
  $end = $_POST['end'];
}

if (isset($_GET['end']))
{
  $end = $_GET['end'];
}

echo '<html><head>
<script language="JavaScript">
function openpopups () {
';
foreach($domains as $value )
{
  if ($value == 'frontal') {
    echo    "window.open('https://".$hostname."/".$end."', '_blank');\n";
  }else{
    echo    "window.open('https://".$hostname."/".$value."/".$end."', '_blank');\n";
  }
}
echo '}
</script>
</head><body>';

echo '
<form methode="post" action="">
<input type="text" name="end" value="'.$end.'" style="width:500px" />
<input type="submit" value="Envoyer" />
</form>
';

foreach($domains as $value )
{
  if ($value == 'frontal') {
    echo '<a href="https://'.$hostname.'/'.$end.'">https://'.$hostname.'/'.$end.'</a><br/>';
  }else{
    echo '<a href="https://'.$hostname.'/'.$value.'/'.$end.'">https://'.$hostname.'/'.$value.'/'.$end.'</a><br/>';
  }
}

echo '<input type="button" value="Open All" onclick="openpopups()">';

echo '</body></html>';

