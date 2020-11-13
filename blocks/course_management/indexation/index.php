<?php
require_once('../../../config.php');
require_once(dirname(__FILE__) . '/../lib/form_helper.php');

$PAGE->set_context(context_course::instance($_GET['id']));

$PAGE->set_url('/blocks/course_management/indexation/index.php', array('id' => $_GET['id']));
$PAGE->set_title('Indexation');
$PAGE->set_heading('Page d\'indexation');
$PAGE->set_pagelayout('standard');
$PAGE->navigation->extend_for_user($USER);

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

$parentcourse = $DB->get_record('course', array('id' => $_GET['id']), '*', MUST_EXIST);
$PAGE->set_course($parentcourse);

require_login();

echo $OUTPUT->header();
if (!isset($_GET['id'])) {
    echo 'Il manque un parametre dans l\'url (l\'id du cours à indéxer)';
    exit;
}

if (!has_capability('block/course_management:index', $PAGE->context, $USER->id, true)) {
    echo '<div id="no_capability">vous ne possédez pas les droits pour réaliser cette action.</div>';
} else {

    $index          = null;
    $levelData      = array();
    $targetData     = array();
    $domainData     = array();
    $academyData    = array();
    $originEspeData = array();
    $departmentData = array();

    try {
        global $DB;
        // fetching index data
        $index = $DB->get_record('indexation_moodle', array('course_id' => $_GET['id']));
        if (!$index) {
            $index = new stdClass();
            $origine_gaia = $DB->get_record('origine_gaia', array('name' => $CFG->academie_name));
        }
        else{
        	$origine_gaia = $DB->get_record('origine_gaia', array('id' => $index->origine_gaia_id));
        }
        // fetching index relations
        foreach (array('level', 'target', 'domain') as $relation) {
            $index->{$relation . 's'} = array();
            $results                  = $DB->get_records(
                'indexation_index_' . $relation,
                array('indexation_id' => @$index->id ?: null),
                '',
                $relation . '_id'
            );
            if ($results && count($results) > 0) {
                foreach ($results as $result) {
                    $index->{$relation . 's'}[] = $result->{$relation . '_id'};
                }
            }
        }

        // fetching relation datas
        $levelData      = object_column($DB->get_records('indexation_level'), 'id', 'name');
        $targetData     = object_column($DB->get_records('indexation_target'), 'id', 'name');
        $domainData     = object_column($DB->get_records('indexation_domain'), 'id', 'name');
        $academyData    = object_column($DB->get_records('t_academie'), 'id', 'libelle');
        $originEspeData = object_column($DB->get_records('t_origine_espe'), 'id', 'name');
        $departmentData = array(0 => array('libelle_long' => '-', 'code_academie' => '*')) + object_column(
            $DB->get_records('t_departement'),
            'id',
            array('libelle_long', 'code_academie')
        );

    } catch (Exception $e) {
        echo 'ERROR: ' . $e->getMessage();
    }

//    $index->academy = null;
//    if (!empty($index->department)) {
//        $index->academy = (int)$departmentData[$index->department]['code_academie'];
//    }

    $collectionData = array(
        'action'        => 'Action',
        'analyse'       => 'Analyse',
        'decouverte'    => 'Découverte',
        'qualification' => 'Qualification',
        'volet_distant' => 'Volet distant',
        'reseau'        => 'Réseau',
        'simulation'    => 'Simulation',
    	'autoformation' => 'Autoformation',
    	'espacecollab'	=> 'Espace collaboratif'
    );

    $originData = array(
        'dgesco'   => 'DGESCO',
        'ih2ef'  => 'IH2EF',
        'academie' => 'Académie',
        'reseau-canope' => 'Canopé',
        'ife'      => 'Ifé',
    	'irem'     => 'IREM',
        'espe'     => 'Espe',
        'dne-foad' => 'DNE-FOAD',
        'autre'    => 'Autre'
    );

    $webServiceUrl = $CFG->hubserver_url. '/webservice/rest/server.php?wstoken=' . $CFG->hubserver_ws_admin_token . '&wsfunction=local_ws_course_magistere_course_magistere&functionname=get_keywords_list';
    ?>

    <h1>Indexation du cours</h1>
    <div id="indexation_error_message">
        Merci de bien vouloir renseigner tous les champs obligatoires.
    </div>

    <form id="indexation_form"
          class="blocks"
          action="<?php echo $CFG->wwwroot ?>/blocks/course_management/indexation/indexation.php"
          method="POST">
    <input type="hidden" name="course_id" value="<?php echo $_GET['id']; ?>">

    <div class="row">
        <div class="column">
            <div class="field">
                <label>Nom du parcours</label>
                <input disabled='disabled' readonly="true" class="readonly_field" type="text"
                       name="nom_parcours" value="<?php echo $parentcourse->fullname; ?>">
            </div>
            <div class="field">
                <label>Mise à jour</label>
                <input class="readonly_field" type="text" disabled="disabled" readonly="true"
                       value="<?php echo isset($index->derniere_maj) ? strftime(
                           'Le %d-%m-%Y à %H:%M',
                           strtotime($index->derniere_maj)
                       ) : 'Non indexé'; ?>">
            </div>
        </div>
        <div class="column">
            <div class="field">
                <label>Description</label>
                <textarea disabled='disabled' readonly="true" class="readonly_field" rows="5" cols="50"
                          name="description"><?php echo strip_tags($parentcourse->summary); ?></textarea>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="column">
        	<table>
        	<tr>
        		<td><label>Ann&eacute;e *</label></td>
        		<td><label>Origine *</label></td>
        		<td><label>Intitul&eacute; *</label></td>
        		<td><label>Version *</label></td>
        	</tr>
        	<tr class="required">
        		<td><input type="text" name="year" class="indexation_year" maxlength="2" pattern=".{2,}"
	                       value="<?php echo isset($index->year) ? $index->year : strftime('%y') ;?>"></td>
	            <td><input class="readonly_field indexation_origin_gaia" name="origin_gaia" type="text" readonly="true"
	                       value="<?php echo $origine_gaia->code; ?>"></td>
	            <td><input type="text" name="title" class="indexation_title" maxlength="15"
	                       value="<?php  echo isset($index->title) ? $index->title : "" ;?>"></td> 
	            <td><input name="version" type="text" class="indexation_version" maxlength="3"
	                       value="<?php echo isset($index->version) ? $index->version : '1.0'; ?>"></td>           
	        </tr>
	        <tr>
	        	<td style=" width:30px;"><span class="help">Deux caractères obligatoires</span></td>
	        	<td></td>
	        	<td><span class="help">Quinze caractères maximums</span></td>
	        	<td><span class="help">Deux caractères séparés par un point (.)</span></td>
	       	</tr>
	            
            </table>
        </div>
        <div class="column">
            <div class="field">
                <label>Identifiant du parcours</label>
               	<input class="readonly_field" name="course_identification" type="text" readonly="true"
                       value="">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="column">
            <div class="field required">
                <label for="objectifs">Objectifs du parcours *
					<span class="objectifs_error" style="display:none">Nombre de caractères supérieurs à 500.</span>
				</label>
                <textarea rows="5" cols="50" id="objectifs"
                          name="objectifs"><?php echo isset($index->objectifs) ? $index->objectifs : ''; ?></textarea>
            </div>
            <fieldset>
                <legend>Origine</legend>
                <div class="field required">
                    <label for="origin">Origine du parcours *</label>
                    <?php echo html_select(
                        $originData,
                        isset($index->origine) ? $index->origine : null,
                        array(
                            'name' => 'origin',
                            'id'   => 'origin',
                        )
                    ); ?>
                </div>
                <div class="field">
                    <label for="academy">Académie</label>
                    <?php echo html_select(
                        $academyData,
                        isset($index->academy) ? $index->academy : null,
                        array(
                            'name' => 'academy',
                            'id'   => 'academy'
                        )
                    ); ?>
                </div>
                <div class="field">
                    <label for="department">Département</label>
                    <?php echo html_select(
                        $departmentData,
                        isset($index->department) ? $index->department : null,
                        array(
                            'name' => 'department',
                            'id'   => 'department'
                        ),
                        function ($key, $value, $attr) {
                            $attr['__text__']     = $value['libelle_long'];
                            $attr['data-academy'] = $value['code_academie'];

                            return $attr;
                        }
                    ); ?>
                </div>
                <div class="field">
                    <label for="academy">Espé</label>
                    <?php echo html_select(
                        $originEspeData,
                        isset($index->origin_espe) ? $index->origin_espe : null,
                        array(
                            'name' => 'origin_espe',
                            'id'   => 'origin-espe'
                        )
                    ); ?>
                </div>
            </fieldset>
            <div class="field">
                <label for="shared-offer">
                    Publier dans
                    <span class="help" title="Ne sont publiés dans l'offre nationale que les parcours
qui ont fait l'objet d'une commande et d'une validation
de la part du ministère de l'éducation">
                        <img src="<?php echo $OUTPUT->image_url('help', 'core'); ?>" alt="" class="iconhelp">
                    </span>
                </label>
                <?php echo html_radio_list(
                    array(
                        0 => 'Offre nationale',
                        1 => 'Offre mutualisée'
                    ),
                    isset($index->shared_offer) ? $index->shared_offer : 0,
                    array(
                        'name' => 'shared_offer',
                        'id'   => 'shared-offer'
                    )
                ); ?>
            </div>

            <div class="field">
                <label>
                    Accompagnement
                    <span class="help" title="Quel niveau d'accompagnement ? Combien de formateurs pour combien de participants ?
Ces éléments facilitent la mise en oeuvre des formations">
                        <img src="<?php echo $OUTPUT->image_url('help', 'core'); ?>" alt="" class="iconhelp">
                    </span>
                </label>
                <textarea cols="50" rows="4" name="accompagnement"><?php echo isset($index->accompagnement) ? $index->accompagnement : ''; ?></textarea>
            </div>
            <div class="field">
                <label for="liste_auteurs">
					Liste des auteurs
					<span class="liste_auteurs_error" style="display:none">Nombre de caractères supérieurs à 160.</span>
				</label>
                <textarea cols="50" rows="4" name="liste_auteurs"><?php echo isset($index->liste_auteurs) ? $index->liste_auteurs : ''; ?></textarea>
            </div>
            <div class="field">
                <label>
                    Contact
                    <span class="email_error" style="display:none">(Adresse m&eacute;l invalide)</span>
                    <span class="help" title="Adresse mél fonctionnelle ou nominative">
                    
                        <img src="<?php echo $OUTPUT->image_url('help', 'core'); ?>" alt="" class="iconhelp">
                    </span>
                </label>
                <input type="text" name="contact_auteurs" value="<?php echo isset($index->contact_auteurs) ? $index->contact_auteurs : ''; ?>"/>
            </div>
            <div class="field">
                <label>
                    Validé par
                    <span class="help" title="Le nom et la fonction de la personne qui a validé le contenu du parcours.
Il s'agit d'un inspecteur ou d'un responsable de formation">
                        <img src="<?php echo $OUTPUT->image_url('help', 'core'); ?>" alt="" class="iconhelp">
                    </span>
                </label>
                <input id="indexation_validation_field" type="text" name="validation"
                       value="<?php echo isset($index->validation) ? $index->validation : ''; ?>">
            </div>
        </div>
        <div class="column">
            <div class="field required">
                <label for="keywords">
                    Mots-clés *
                    <span class="help" title="Les mots clés permettent de rechercher un parcours dans
l'offre. C'est l'occasion de donner des précisions sur
la discipline, la classe concernée, les compétences
professionnelles travaillées ou le sujet couvert par le
parcours.

Quelques exemples :

Exemple 1 : 6ème, technologie
Exemple 2 : pédagogie, inversée
Exemple 3 : continuité pédagogique, remédiation, PPR
Exemple 4 : STL, littérature">
                        <img src="<?php echo $OUTPUT->image_url('help', 'core'); ?>" alt="" class="iconhelp">
                    </span>
                </label>
                <textarea rows="4" cols="50" id="keywords"
                          name="keywords"><?php echo isset($index->keywords) ? implode(', ', explode('|', trim($index->keywords, '|'))) : ''; ?></textarea>
                <span class="help">Séparez chaque mot-clé par une virgule (,)</span>
            </div>
            <fieldset>
                <legend>Démarche</legend>
                <div class="field required">
                    <label for="collection">
                        Collection *
                        <span class="help" title="Aide sur les collections à définir">
                            <img src="<?php echo $OUTPUT->image_url('help', 'core'); ?>" alt="" class="iconhelp">
                        </span>
                    </label>
                    <?php echo html_select(
                        $collectionData,
                        isset($index->collection) ? $index->collection : null,
                        array(
                            'name' => 'collection',
                            'id'   => 'collection'
                        )
                    ); ?>
                </div>
                <?php
                if (isset($index->tps_a_distance)) {
                    $tps_a_distance_min  = (int)$index->tps_a_distance % 60;
                    $tps_a_distance_hour = (int)floor($index->tps_a_distance / 60);
                }

                if (isset($index->tps_en_presence)) {
                    $tps_en_presence_min  = (int)$index->tps_en_presence % 60;
                    $tps_en_presence_hour = (int)floor($index->tps_en_presence / 60);
                }
                ?>
                <div class="row">
                    <div class="column">
                        <div class="field">
                            <label>
                                Temps à distance
                                <span class="help" title="Les informations horaires précises permettent de faciliter
la mise en oeuvre du parcours. Pour le 1er degré, ces
informations servent de base pour les attestations de
présence émises à la fin de la formation">
                                    <img src="<?php echo $OUTPUT->image_url('help', 'core'); ?>" alt="" class="iconhelp">
                                </span>
                            </label>
                            <input id="field_tps_a_distance_hour" class="time_field" type="text"
                                   name="tps_a_distance_hour"
                                   value="<?php echo isset($index->tps_a_distance) ? $tps_a_distance_hour : ''; ?>">
                            h
                            <input id="field_tps_a_distance_min" class="time_field" type="text"
                                   name="tps_a_distance_min"
                                   value="<?php echo isset($index->tps_a_distance) ? $tps_a_distance_min : ''; ?>">
                            min
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label>Temps en présence</label>
                            <input type="text" id="field_tps_en_presence_hour" class="time_field"
                                   name="tps_en_presence_hour"
                                   value="<?php echo isset($index->tps_en_presence) ? $tps_en_presence_hour : ''; ?>">
                            h
                            <input type="text" id="field_tps_en_presence_min" class="time_field"
                                   name="tps_en_presence_min"
                                   value="<?php echo isset($index->tps_en_presence) ? $tps_en_presence_min : ''; ?>">
                            min
                        </div>
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend>Public cible</legend>
                <div class="field required">
                    <label>Niveau d'exercice*</label>
                    <?php echo html_checkbox_list(
                        $levelData,
                        $index ? $index->levels : array(),
                        array(
                            'name' => 'levels[]',
                            'id'   => 'levels'
                        )
                    ); ?>
                </div>
                <div class="field required">
                    <label>Fonction *</label>
                    <?php echo html_checkbox_list(
                        $targetData,
                        $index ? $index->targets : array(),
                        array(
                            'name' => 'targets[]',
                            'id'   => 'targets'
                        )
                    ); ?>
                </div>
                <div class="field required">
                    <label>Domaine *</label>
                    <?php echo html_checkbox_list(
                        $domainData,
                        $index ? $index->domains : array(),
                        array(
                            'name' => 'domains[]',
                            'id'   => 'domains'
                        )
                    ); ?>
                </div>
            </fieldset>
        </div>
    </div>

    <input type="submit" value="Enregistrer">
    <button id="cancel_indexation_button" type="button">Annuler</button>
    </form>
    <!-- JAVASCRIPT -->
    <script>
        $(function () {
            //On vérifie que tous les champs obligatoires sont remplis avant submit
            $('#indexation_form').submit(function () {
                var $form = $(this);
                var errors = [];

                $form.find('.required').removeClass('fail');

                // get all required field names
                var fields = $form.find('.required [name]').map(function () {
                    return this.name;
                }).get();

                // remove all redundant fields (checkboxes)
                fields = fields.filter(function (item, pos, self) {
                    return self.indexOf(item) == pos;
                });

                // check if all mandatory fields are filled
                $.each(fields, function () {
                    var $input = $form.find('[name=' + this.replace(/(\[|\])/g, '\\$1') + ']');
                    if (
                        ($input.attr('type') !== 'checkbox' && $input.val().length === 0)
                            || ($input.attr('type') === 'checkbox' && $input.filter(':checked').length === 0)
                            || ($(".indexation_year").val().length <= 1)
                        ) {
                        errors.push($input);
                    }
                });

                var $email = $form.find('[name=contact_auteurs]');
                if($email){
                    if($email.val() !== ""){
                    	var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                        if(re.test($email.val()) == false){
        					errors.push($email);
        					$email.prev('label').addClass('fail');
                        	$form.find('.email_error').show();
                        }else{
                        	$email.prev('label').removeClass('fail');
                        }
                    }
                }
                
                var $objectifs = $form.find('[name=objectifs]');
				if($objectifs){
					if($objectifs.val().length >= 500){
						errors.push($objectifs);
						$form.find('.objectifs_error').show();
					}
					else{
						$objectifs.prev('label').removeClass('fail');
					}
				}
				
				var $liste_auteurs = $form.find('[name=liste_auteurs]');
				if($liste_auteurs){
					if($liste_auteurs.val().length >= 160){
						errors.push($liste_auteurs);
						$liste_auteurs.prev('label').addClass('fail');
						$form.find('.liste_auteurs_error').show();
					}
					else{
						$liste_auteurs.prev('label').removeClass('fail');
					}
				}

                $.each(errors, function () {
                    this.parents('.required').addClass('fail');
                });

                if (errors.length) {
                    $('#indexation_error_message').show();
                }

                return !errors.length;
            });

            $('#origin').each(function () {
                var $select = $(this),
                    $academy = $('#academy').parents('.field'),
                    $department = $('#department').parents('.field'),
                    $originEspe = $('#origin-espe').parents('.field');

                $select.bind('change', function () {
                    $academy.hide();
                    $department.hide();
                    $originEspe.hide();

                    switch ($select.val()) {
                        case 'academie':
                            $academy.show();
                            $department.show();
							code_gaia_using_ajax($('#academy').val());
                            break;
                        case 'espe':
                            $originEspe.show();
							code_gaia_using_ajax($select.val());
                            break;
						default:
							code_gaia_using_ajax($select.val());
                    }
                });

                $select.trigger('change');
            });

            $('#academy').each(function () {
                var $academy = $(this),
                    $department = $('#department'),
                    options = {},
                    initValue = $department.val();

                $department.find('option').each(function () {
                    var $opt = $(this);
                    options[$opt.val()] = {name: $opt.text(), academy: $opt.attr('data-academy')};
                });

                $academy.bind('change', function () {
                    $department.empty();

                    $.each(options, function (key, value) {
                        if (value.academy === $academy.val() || value.academy === '*') {
                            $department.append('<option value="' + key + '">' + value.name + '</option>');
                        }
                    });

                    if (!initValue) {
                        initValue = $department.find('option:first').val();
                    }

                    $department.val(initValue);
                    initValue = null;
                });

                $academy.trigger('change');
            });

            $('#keywords').each(function () {
                var $kw = $(this),
                    webServiceUrl = '<?php echo $webServiceUrl; ?>';

                function split (val) {
                    return val.split(/,\s*/);
                }

                function extractLast(term) {
                    return split(term).pop();
                }

                $kw.autocomplete({
                    source: function (request, response) {
                        $.support.cors = true;
                        $.ajax({
                            url: webServiceUrl,
                            crossDomain: true,
                            dataType: 'xml',
                            data: {
                                textparam: extractLast(request.term)
                            },
                            success: function (s) {
                                var data = $.parseJSON($(s).find('VALUE').text());
                                response(data.keywords);
                            },
                            error: function (s) {
                                console.dir(s);
                            }
                        });
                    },
                    search: function () {
                        var term = extractLast(this.value);
                        if (term.length < 2) {
                            return false;
                        }
                    },
                    focus: function () {
                        return false;
                    },
                    select: function (event, ui) {
                        var terms = split(this.value);
                        terms.pop();
                        terms.push(ui.item.value);
                        terms.push('');
                        this.value = terms.join(', ');

                        return false;
                    }
                });
            });

            //on autorise seulement les valeurs numériques
            $('input.time-field').keydown(function (event) {
                // On autorise: espace, suppr, tab, echap, et enter
                if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 13 ||
                    // On autorise: Ctrl+A
                    (event.keyCode == 65 && event.ctrlKey === true) ||
                    // autorise: home, Fin, gauche, droite
                    (event.keyCode >= 35 && event.keyCode <= 39)) {
                    // let it happen, don't do anything
                    return;
                }
                else {
                    // Ensure that it is a number and stop the keypress
                    if (event.shiftKey && ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
                        event.preventDefault();
                    }
                }
            });
        });

    </script>
    <script>
    	function change_course_identification(origin_gaia){
    		var year = $( "input[name$='year']").val();
    		if(origin_gaia == null){
    			var origin_gaia = $( "input[name$='origin_gaia']").val();
    		}
    		else{
    			$( "input[name$='origin_gaia']").val(origin_gaia);
            }
	        var title = $( "input[name$='title']").val();
	        var version = $( "input[name$='version']").val();
	        if(title != ""){
				var course_identification = year + "_" + origin_gaia + "_" + title.toUpperCase() + "_" + version;
				$( "input[name$='course_identification']").val(course_identification);
		    }
	        else{
	        	$( "input[name$='course_identification']").val("...");
	      	}
        }
		function code_gaia_using_ajax(value){
			$.ajax({
				url: "ajax_select.php",
				type: "post",
				data: {value: value},
				datatype: 'json',
				  success: function(data){
					  var data = $.parseJSON(data);
					  change_course_identification(data.code);           
				  },
				  error:function(){
					  console.log('There was an error.');
				  }   
			});
		}
	    $(function() {
	    	change_course_identification();
	    	$( "input[name$='title']").change(function() {change_course_identification();});
			$( "input[name$='year']").change(function() {change_course_identification();});
			$( "input[name$='version']").change(function() {change_course_identification();});
	    	$("#academy").change(function(){
				var academy = $( "#academy").val();
		    	code_gaia_using_ajax(academy);
			});

			$('#cancel_indexation_button').click(function(){
                window.location = "<?php echo $CFG->wwwroot.'/course/view.php?id='.$_GET['id'];?>";
            });
	    });
    </script>
<?php
}
echo $OUTPUT->footer();
?>