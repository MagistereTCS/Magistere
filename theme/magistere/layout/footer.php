<?php

if (empty($PAGE->layout_options['nofooter'])) {
require_once($CFG->libdir .'/versionmagisterelib.php');
require_once($CFG->libdir .'/vm_identification.php');
require_once($CFG->dirroot .'/local/magisterelib/FooterLib.php');

$host = $CFG->magistere_domaine;


?>
<div id="page-footer-content">
    <input type="hidden" id="ttt_url" value="<?php echo $CFG->wwwroot.'/local/magisterelib/footer_ajax_url_search.php';?>">
    <div id="page-footer-options">
        <div class="container-fluid">
            <div id="logo_footer">
                <div id="logo_ministere"></div>
            </div>
            <div id="footer_elements">
                <div class="element">
                    <ul>
                        <li><?php //include('Version_ALTI.txt');//include temporaire afin de visualiser la version dispo à la racine ?></li>
                        <li><a id="footer_terms" href="<?php echo FooterLib::get_page_url(FooterLib::PAGE_TERMS); ?>">Mentions légales</a></li>
                        <li>|</li>
                        <li><a id="footer_about" href="<?php echo FooterLib::get_page_url(FooterLib::PAGE_ABOUT); ?>">À propos</a></li>
                        <li>|</li>
                        <li><a id="footer_contact" href="<?php echo FooterLib::get_page_url(FooterLib::PAGE_CONTACT); ?>">Contact</a></li>
                        <li>|</li>
                        <li><a class="footer_conhelp" href="<?php echo FooterLib::get_page_url(FooterLib::PAGE_CONHELP); ?>">Aide à la connexion</a></li>
                        <li>|</li>
                        <li><a href="<?php echo $CFG->wikiUrlAccueil; ?>">Aide à l'utilisation</a></li>
                    </ul>
                </div>
                <div class="element">
                    <ul>
                        <li class="title">Plateformes du réseau :</li>
                        <li><a href="<?php echo $host; ?>/dgesco/">DGESCO</a></li>
                        <li>|</li>
                        <li><a href="<?php echo $host; ?>/esen/">IH2EF</a></li>
                        <li>|</li>
                        <li><a href="<?php echo $host; ?>/reseau-canope/">Réseau Canopé</a></li>
                        <li>|</li>
                        <li><a href="<?php echo $host; ?>/dne-foad/">DNE</a></li>
                        <li>|</li>
                        <li>
                            <div class="plate-list" id="plat_list">
                                <form>
                                    <select name="" id="selectaca">
                                        <option class="option_plat_list" value="0">Autre Plate-forme</option>
                                        <?php
                                        foreach ( $CFG->academylist as $key=>$value )
                                        {
                                            if($key != 'frontal' && $key != 'ac-caen'){
                                                echo '<option class="option_plat_list" value="'.$host.'/'.$key.'/">'.$value['name'].'</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </form>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="element">
                    <ul>
                        <li><?php printversion();?></li>
                        <li>|</li>
                        <li><?php echo get_vm_id();?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    //redirection suite à la selection dans la combobox
    $(function() {

        $('#selectaca').change(function(){
            url = $('#selectaca').val();
            if(url!=0){
                window.location.href = url;
            }
        });

    });
</script>

<?php

$bl = array('89.225.219.100','89.225.219.102');

$isbl = isset($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'],$bl);

if ($CFG->magistere_domaine == 'https://magistere.education.fr' && !$isbl) {
?>

<script type="text/javascript">
  var _paq = _paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//wa.phm.education.gouv.fr/magistere/";
    _paq.push(['setTrackerUrl', u+'p.php']);
    _paq.push(['setSiteId', '1']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'p.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript>
<img src='https://wa.phm.education.gouv.fr/magistere/p.php?idsite=1' style='border:0;' alt='' />
</noscript>

<?php //
//}else if ($CFG->magistere_domaine == 'https://pp-magistere.foad.hp.in.phm.education.gouv.fr' && !$isbl) {
//?>
<!--    <script type="text/javascript">-->
<!--    var _paq = _paq || [];-->
<!--    _paq.push(['trackPageView']);-->
<!--    _paq.push(['enableLinkTracking']);-->
<!--    (function() {-->
<!--        var u="//qt-wa.phm.education.gouv.fr/qualif/";-->
<!--        _paq.push(['setTrackerUrl', u+'p.php']);-->
<!--        _paq.push(['setSiteId', '28']);-->
<!--        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];-->
<!--        g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'p.js'; s.parentNode.insertBefore(g,s);-->
<!--    })();-->
<!--    </script>-->
<!--    <noscript>-->
<!--    <img src="//qt-wa.phm.education.gouv.fr/qualif/p.php?idsite=28&rec=1" style="border:0;" alt="" />-->
<!--    </noscript>-->
<?php } ?>


<?php } ?>