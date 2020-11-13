<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->dirroot .'/local/magisterelib/FooterLib.php');

class FrontalFeatures
{

	
	function __construct()
	{
		
	}
	
	public static function set_page_requirements()
	{
		global $PAGE;
		
		$PAGE->requires->jquery();
		$PAGE->requires->jquery_plugin('ui');
		$PAGE->requires->jquery_plugin('ui-css');
		
	}
	
	public static function get_frontal_connection_page()
	{
		global $CFG, $OUTPUT;
		
		require_once($CFG->dirroot.'/local/magisterelib/UserSetMainAcademyForm.php');
		
		$main_aca_form = new UserSetMainAcademyForm();
		
		$magistere_academy_shibboleth = get_academies_shibboleth_config();

        $is_institution = false;
        if($CFG->academie_name == 'reseau-canope' || $CFG->academie_name == 'efe' || $CFG->academie_name == 'ih2ef' || $CFG->academie_name == 'dgesco' || $CFG->academie_name == 'dgrh' || $CFG->academie_name == 'dne-foad' || isfrontal()){
            $is_institution = true;
        }
        
        $out = '';
		if($main_aca_form->is_submitted()){
			$data = $main_aca_form->get_submitted_data();

			if (!isloggedin())
			{
				if(isset($magistere_academy_shibboleth[$data->main_academy]) && $data->validate){
					if($data->main_academy == 'reseau-canope' || $data->main_academy == 'efe' || $data->main_academy == 'ih2ef' || $data->main_academy == 'dgesco' || $data->main_academy == 'dne-foad'){
						$url = $magistere_academy_shibboleth[$data->main_academy]['sco_link'];
					}
					else{
						$url = $magistere_academy_shibboleth[$data->main_academy]['ARENA'];
					}
					
					redirect($url);
					exit;
				}
			}else{
				
				if($data->validate){
					user_set_mainacademy($data->main_academy, $USER->id);
					
					echo '<div id="redirect_waiting_popup">
				<p>Redirection en cours...</p>
			</div>
			<script>
				$("#redirect_waiting_popup").dialog({
					modal: true,
					resizable: false,
					draggable: false,
					width: "600px",
					dialogClass: "popup_frontal",
					closeOnEscape: false,
					open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog | ui).hide(); }
				});
			</script>';
					
					//Redirection si deeplink present
					if (($mm_sb_redirection = mmcached_get('mmid_session_'.get_mmid().'_hub_redirection')) !== false)
					{
						error_log('Redirection memcached : '.$mm_sb_redirection);
						// On supprime la redirection pour eviter que la redirection se repete
						mmcached_delete('mmid_session_'.get_mmid().'_hub_redirection');
						redirect($mm_sb_redirection);
					}else{
						//Redirection vers l'academie de rattachement
						$magistere_academy = get_magistere_academy_config();
						$url = $CFG->magistere_domaine.$magistere_academy[$data->main_academy]['accessdir'].'/';
						redirect($url);
					}
				}
			
			}
			
			
			
			
		}
		
		$out .= '
		<div id="magistere_accueil_main_page">
		    <div class="connection">
            <h1 class="title">Connectez-vous à M@gistère</h1>
            <h2 class="subtitle">VOTRE PLATEFORME DE FORMATION CONTINUE</h2>
            <div>
                <div class="block ens_sco">
                <img src="'.$OUTPUT->image_url('general/accueil_avatar', 'theme').'">';
		
		$sco_url = $CFG->shibboleth_hub_url_sco;
		if (!$is_institution)
		{
		    $sco_url = $CFG->academylist[$CFG->academie_name]['ARENA'];
		}
		
		$isaca = false;
		if (isfrontal())
		{
			$out .= '<h2>Je suis enseignant ou personnel d\'un établissement dépendant de l\'<b>enseignement scolaire</b></h2>';
			$out .= '<a class="btn" id="btn_sco" href="'.$sco_url.'"><span>Connexion</span></a>';
		}else if ($CFG->academie_name == 'reseau-canope' || $CFG->academie_name == 'ih2ef' || $CFG->academie_name == 'dgesco' || $CFG->academie_name == 'dne-foad')
		{
			$out .= '<h2>Je suis enseignant ou personnel d\'un établissement scolaire ou de l\'<b>administration centrale</b></h2>';
			$out .= '<a class="btn" id="btn_sco" href="'.$sco_url.'"><span>Connexion</span></a>';
		}else if ($CFG->academie_name == 'efe')
		{
			$out .= '<h2>Je suis enseignant ou personnel fran&ccedil;ais dans un <b>établissement à l\'étranger</b></h2>';
			$out .= '<a class="btn" id="btn_sco" href="'.$CFG->wwwroot.'/login"><span>Connexion</span></a>
            <div class="link_connexion">
			<span><a class="underline"  href="'.$CFG->shibboleth_hub_url_sco.'"><i class="fa fa-play" aria-hidden="true"></i> Je ne suis pas un enseignant ou personnel français à l\'étranger</a></span>
		</div>';
		}else 
		{
			$isaca = true;
			$out .= '<h2>Je suis enseignant ou personnel d\'un établissement dépendant <b>'.$magistere_academy_shibboleth[$CFG->academie_name ]['index'].'</b></h2>
			<a class="btn" id="btn_sco" href="'.$sco_url.'"><span>Connexion</span></a>
			<div class="link_connexion">
			<span><a class="underline"  href="'.$CFG->shibboleth_hub_url_sco.'"><i class="fa fa-play" aria-hidden="true"></i> Je ne suis pas '.$magistere_academy_shibboleth[$CFG->academie_name]['index'].'</a></span>
		</div>';
		}

		$scomsg = '<b>Exemples</b> : j\'ai une adresse mél professionnelle de type prenom.nom@'.$CFG->academie_name.'.fr';


		if(strpos($CFG->academie_name, 'ac-') === false){
            $scomsg = '<b>Exemples</b> : j\'ai une adresse mél professionnelle de type prenom.nom@ac-academie.fr, prenom.nom@education.gouv.fr ou prenom.nom@igesr.gouv.fr';
        }


		$out .= '   <a class="link-info" data-toggle="collapse" href="#collapse_ens_sco" role="button" aria-expanded="false" aria-controls="collapse_ens_sco">
                        <i class="fa fa-info-circle fa-2x" aria-hidden="true"></i>
                    </a> 
                <div class="collapse " id="collapse_ens_sco">
                    <div class="bg">
                        <span>
                            '.$scomsg.'
                        </span>
                        <br/>					
                    </div>
                </div>
            </div>
				
			<div class="block ens_sup">
				<img src="'.$OUTPUT->image_url('general/accueil_avatar2', 'theme').'">
				<h2>Je suis enseignant ou étudiant d\'un établissement dépendant de l\'<b>enseignement supérieur</b></h2>
				<a class="btn" id="btn_sup" href="'.$CFG->shibboleth_hub_url_sup.'">
					<span>Connexion</span>
				</a>'.($isaca?'<div class="link_connexion"></div>':'').'
				    <a class="link-info" data-toggle="collapse" href="#collapse_ens_sup" role="button" aria-expanded="false" aria-controls="collapse_ens_sup">
                        <i class="fa fa-info-circle fa-2x" aria-hidden="true"></i>
                    </a> 
                    <div class="collapse " id="collapse_ens_sup">
                        <div class="bg">
                            <span><b>Exemples</b> : j\'ai une adresse mél professionnelle fournie par une université, un INSPE, un établissement de l\'enseignement supérieur, une organisation telle que Canopé, l\'ONISEP ou l\'enseignement agricole</span>
                            <br/>
                        </div>
					</div>
					</div>
					</div>
		<div class="other-connections">
			<div class="help_connection">Besoin <a class="footer_conhelp" href="'.FooterLib::get_page_url(FooterLib::PAGE_CONHELP).'"> d\'aide pour vous connecter ?</a></div>
			<div class="other_connection">ou <a href="'.$CFG->wwwroot.'/login"> connexion directe à M@gistère</a></div>
		</div>
	</div>
	<div class="info">
		<h1 class="title">M@gistère en quelques mots</h1>
		<div>
			<div class="block icon1">
				<img src="'.$OUTPUT->image_url('general/accueil_icon1', 'theme').'">
				<h2><b>Une plateforme unique</b><br/>permettant de vous former<br/>où et quand vous le souhaitez</h2>
			</div>
			<div class="block icon2">
				<img src="'.$OUTPUT->image_url('general/accueil_icon2', 'theme').'">
				<h2><b>Une offre de formation</b><br/>personnalisée où vous avez<br/>le choix</h2>
			</div>
			<div class="block icon3">
				<img src="'.$OUTPUT->image_url('general/accueil_icon3', 'theme').'">
				<h2><b>Des parcours de qualité</b><br/>accompagnés ou en<br/>auto-formation répondant à vos<br/>besoins immédiats de formation</h2>
			</div>
		</div>
	</div>
</div>';
		
		
		
		return $out;
	
	}
	
	public static function get_main_aca_code($main_aca_form)
	{
		global $CFG, $USER, $OUTPUT, $PAGE;
		
		if (!isset($USER->auth))
		{
			$USER->auth = '';
		}
		
		$PAGE->requires->jquery_plugin('vmap');
		$PAGE->requires->jquery_plugin('vmap-france');
		$PAGE->requires->jquery_plugin('vmap-css');
		
		$out = '
		<div id="fill_main_aca" class="rattach" title="Sélectionner votre instance favorite">
		    '.(($USER->auth == "shibboleth" && (user_get_mainacademy($USER->id) === false || user_get_mainacademy($USER->id) == ''))?
		        '<p class="infomap"><img src="'.$OUTPUT->image_url('general/dommap/green_info','theme').'" />Vous pourrez r&eacute;initialiser ce choix &agrave; tout moment dans votre profil</p>':'').'
		            
			<div id="plateforme_aca" class="accueil_instit">
				<h2>Instances acad&eacute;miques</h2>
				<div id="francemap" style="height: 300px"></div>
		            
				<div id="dommap">
					<p class="domrow">
						<a href="#ac-martinique" class="link_dommap info"><img title="Martinique" src="'.$OUTPUT->image_url('general/dommap/martinique_blue', 'theme').'"/></a>
						<a href="#ac-noumea" class="link_dommap info"><img title="Nouvelle-Cal&eacute;donie" src="'.$OUTPUT->image_url('general/dommap/nouvelle_caledonie_blue', 'theme').'"/></a>
						<a href="#ac-reunion" class="link_dommap info"><img title="R&eacute;union" src="'.$OUTPUT->image_url('general/dommap/reunion_blue', 'theme').'"/></a>
					</p>
					<p class="domrow">
						<a href="#ac-guadeloupe" class="link_dommap info"><img title="Guadeloupe" src="'.$OUTPUT->image_url('general/dommap/guadeloupe_blue', 'theme').'"/></a>
						<a href="#ac-mayotte" class="link_dommap info"><img title="Mayotte" src="'.$OUTPUT->image_url('general/dommap/mayotte_blue', 'theme').'"/></a>
						<a href="#ac-polynesie" class="link_dommap info"><img title="Polyn&eacute;sie fran&ccedil;aise" src="'.$OUTPUT->image_url('general/dommap/polynesie_francaise_blue', 'theme').'"/></a>
					</p>
					<p class="domrow">
						<a href="#ac-wallis-futuna" class="link_dommap info"><img title="Wallis-et-Futuna" src="'.$OUTPUT->image_url('general/dommap/wallis_et_futuna_blue', 'theme').'"/></a>
						<a href="#ac-guyane" class="link_dommap info"><img title="Guyane" src="'.$OUTPUT->image_url('general/dommap/guyane_blue', 'theme').'"/></a>
						<a href="#ac-st-pierre-miquelon" class="link_dommap info"><img title="Saint-Pierre-et-Miquelon" src="'.$OUTPUT->image_url('general/dommap/st_pierre_et_miquelon_blue', 'theme').'"/></a>
					</p>
				</div>
			</div>
						    
			<div id="platforme_instit">
				<h2>Instances nationales</h2>
				<p>
					<a href="#dgrh" class="link_aca">
						<span class="title">
		                	DGRH
			            </span>
						<br/>
		                Direction g&eacute;n&eacute;rale des ressources humaines
					</a>
					<br/>
					<a href="#dgesco" class="link_aca">
						<span class="title">
		                	DGESCO
			            </span>
						<br/>
		                Direction G&eacute;n&eacute;rale de l\'Enseignement Scolaire
					</a>
					<br/>
					<a href="#dne-foad" class="link_aca">
						<span class="title">DNE</span>
						<br/>
						Direction du num&eacute;rique &eacute;ducatif
					</a>
					<br/>
					<a href="#ih2ef" class="link_aca">
						<span class="title">IH2EF</span>
						<br/>
						Institut des hautes &eacute;tudes de l\'&eacute;ducation et de la formation
					</a>
					<br/>
					<a href="#efe" class="link_aca">
					<span class="title">&Eacute;F&Eacute;</span>
					<br/>
		                &Eacute;tablissement français à l\'&Eacute;tranger
					</a>
					<br/>
					<a href="#reseau-canope" class="link_aca">
						<span class="title">R&eacute;seau Canop&eacute;</span>
					</a>
				</p>
			</div>
			'.$main_aca_form->render().'</div>';
		
		$PAGE->requires->js_call_amd("local_magisterelib/frontal_mainacaDialog", "init");
		
		return $out;
	}

    /**
     * @param $capability
     * @param $aca academy code
     *
     * check if the current user has the capability on the academy (works only for system context)
     *
     * @return
     */
    static function has_capability($aca, $capability, $context =null, $user=null)
    {

        global $USER;
        if ($user == null) {$user = $USER;}

        if(is_siteadmin()) {
            return true;
        }

        if($user->auth != 'shibboleth'){
            return false;
        }

        if(!is_int($context)){
            return false;
        }

        if ((databaseConnection::instance()->get($aca)) === false){
            return false;
        }

        if(($useraca = databaseConnection::instance()->get($aca)->get_record('user', array('username' => $user->username))) === false){
            return false;
        }

        $hascap = databaseConnection::instance()->get($aca)->get_records_sql('SELECT ra.id
FROM {role_assignments} ra
INNER JOIN {role_capabilities} rc ON rc.roleid=ra.roleid
WHERE ra.userid=? AND rc.capability=?'.($context!=null?' AND ra.contextid = ?':''), array($useraca->id, $capability, $context));

        return (count($hascap) > 0);
    }

    static function user_has_role_assignment($user, $shortname, $aca)
    {
    	if($user->auth != 'shibboleth'){
    		return false;
    	}
    	
    	if ((databaseConnection::instance()->get($aca)) === false){
    		return false;
    	}
    	
    	if(($useraca = databaseConnection::instance()->get($aca)->get_record('user', array('username' => $user->username))) === false){
    		return false;
    	}
    	
    	if(($role = databaseConnection::instance()->get($aca)->get_record('role', array('shortname' => $shortname))) === false){
    		return false;
    	}
    	
    	$hasrole = databaseConnection::instance()->get($aca)->get_records_sql('SELECT ra.id
FROM {role_assignments} ra
WHERE ra.roleid=? AND ra.userid=?', array($role->id, $useraca->id));
    	
    	return (count($hasrole) > 0);
    }
    
    static function user_is_formateur($userid, $roleshortname, $acaname)
    {
    	
    	if ((databaseConnection::instance()->get($acaname)) === false){
    		return false;
    	}
    	
    	if(($role = databaseConnection::instance()->get($acaname)->get_record('role', array('shortname' => $roleshortname))) === false){
    		return false;
    	}
    	
    	$hasrole = databaseConnection::instance()->get($acaname)->get_records_sql('SELECT ra.id
FROM {role_assignments} ra
WHERE ra.roleid=? AND ra.userid=?', array($role->id, $userid));
    	
    	return (count($hasrole) > 0);
    }

}
