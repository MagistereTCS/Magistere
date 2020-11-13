<?php

require_once($CFG->dirroot.'/lib/coursecatlib.php');
require_once($CFG->dirroot.'/blocks/course_migration/BaseConvertor.php');
require_once($CFG->dirroot.'/blocks/course_migration/lib.php');

/**
 * Class FilterDisplay
 *
 * Used to format data for jtable
 */
class FilterDisplay {
    private $config;

    public function __construct($filterconfig)
    {
        $this->config = $filterconfig;
    }

    public function formatData(&$data)
    {
        global $CFG;

        foreach($data as &$course)
        {
            $courseurl = new moodle_url($CFG->magistere_domaine.'/'.$course->aca_name.'/course/view.php', [
                'id' => $course->localid
            ]);

            $course->fullname = html_writer::link($courseurl, $course->fullname);


            $course->format = get_string($course->format.'label', 'local_supervision_tool');

            // var_dump($course->migrationstatus);
            list($migrationlabel, $migrationdata) = $this->getMigrationLabelData($course->migrationstatus, $course->migrationdate, $course->validated, $course->stdcourseid);

            if($migrationlabel){
                $course->migrationstatus = get_string($migrationlabel, 'local_supervision_tool', $migrationdata);

                if(!$course->validated){
                    $course->migrationstatus .= '<br/>';

                    $label = get_string($course->migrationoriginalformat.'label', 'local_supervision_tool');

                    $course->migrationstatus .= html_writer::link(new moodle_url('/course/view.php', array('id' => $course->flexcourseid)), $label);
                }

            }

            if($course->startdate){
                $course->startdate = date('d-m-Y', $course->startdate);
            }

            if($course->publicationdate){
                $course->publicationdate = date('d-m-Y', $course->publicationdate);
            }

            if($course->publicationdateorigine){
                $course->publicationdateorigine = date('d-m-Y', $course->publicationdateorigine);
            }

            if($course->enddate){
                $course->enddate = date('d-m-Y', $course->enddate);
            }

            if($course->migrationdate){
                $course->migrationdate = date('d-m-Y', $course->migrationdate);
            }

            // format frommigrationdate and set the result in the migrationdate attribute
            // normally only flexpage course have frommigrationdate filled
            if($course->frommigrationdate){
                $d = explode(',', $course->frommigrationdate);
                for($i=0; $i<count($d); $i++){
                    $d[$i] = date('d-m-Y', $d[$i]);
                }

                $course->migrationdate = implode('<br/>', array_keys(array_flip($d)));
            }

            if($course->convertedformat){
                $course->migrationstatus = '';

                $cf = explode(',', $course->convertedformat);
                $ids = explode(',', $course->stdcourseid);
                $fms = explode(',', $course->frommigrationstatus);
                $fmd = explode(',', $course->frommigrationdate);
                $fmv = explode(',', $course->frommigrationvalidated);
                $links = array();

                for($i=0; $i<count($cf); $i++){

                    $label = get_string($cf[$i].'label', 'local_supervision_tool');

                    if(isset($fmd[$i]) == false){
                        $fmd[$i] = 0;
                    }

                    list($migrationlabel, $migrationdata) = $this->getMigrationLabelData($fms[$i], $fmd[$i], $fmv[$i]);

                    if($migrationlabel){
                        $links[] = get_string($migrationlabel, 'local_supervision_tool', $migrationdata);
                    }

                    if(isset($ids[$i])){
                        $links[] = html_writer::link(new moodle_url('/course/view.php',  array('id' => $ids[$i])), $label);
                    }else{
                        $links[] = $label;
                    }
                }

                $course->migrationstatus .= implode('<br/>', $links);
            }

            if($course->lastaccess){
                $d = explode(',', $course->lastaccess);
                for($i=0; $i<count($d); $i++){
                    $d[$i] = date('d-m-Y', $d[$i]);
                }

                $course->lastaccess = implode('<br/>', array_keys(array_flip($d)));
            }

            if($course->publicationtype !== null){

                $label = 'publishedoncourseofferlabel';
                if($course->publicationtype == FilterParams::PUBLICATION_FORMATION_OFFER
                || $course->publicationtype == FilterParams::PUBLICATION_FORMATION_LOCAL_OFFER){
                    $label = 'publishedonformationofferlabel';
                }

                $url = new moodle_url($CFG->magistere_domaine.'/'.$course->aca_name.'/local/workflow/index.php?id=', [
                    'id' => $course->localid
                ]);

                $course->publicationtype = html_writer::link($url, get_string($label, 'local_supervision_tool'));
            }

            $categoryurl = new moodle_url($CFG->magistere_domaine.'/'.$course->aca_name.'/course/management.php', [
                'categoryid' => $course->category
            ]);
            $course->category = html_writer::link($categoryurl, $course->categoryname);


            if($course->comment){
                $course->comment = nl2br($course->comment);
            }

            $version = '';
            if($course->timemodifiedcoursehub && $course->timemodifiedcoursehub != $course->timemodifiedcourse){
                $version .= get_string('versioncoursemodifiedalert', 'local_supervision_tool');
            }

            if($course->timemodifiedhubindexation && $course->timemodifiedhubindexation != $course->timemodifiedlocalindexation){
                if($version){
                    $version .= "\n";
                }

                $version .=  get_string('versionindexationmodifiedalert', 'local_supervision_tool');
            }

            $course->version = ($version ? $version : null);

            if($course->publisher){
                $course->publisher = html_writer::link('mailto:'.$course->publisheremail, $course->publisher);
            }
            
            
            
            if($course->formateurs) {
                $formateursarray = explode(',', $course->formateurs);
                $selectedformateurs = '';
                for($i = 0; $i < 3; $i++) {
                    if (!isset($formateursarray[$i])) {
                        break;
                    }
                    $selectedformateurs .= $formateursarray[$i];
                    if (isset($formateursarray[$i+1])) {
                        $selectedformateurs .= ($i < 2) ? ', ' : '(...)';
                    }
                }
                $course->formateurs = '<button type="button" class="formateurs" data-formateurs-id="'.$course->formateurs_id.'">'.$selectedformateurs.'</button>';
            }

            if(!$this->config->depth){
                unset($course->depth);
            }

            if(!$this->config->lastAccess){
                unset($course->lastaccess);
            }

            if(!$this->config->startDate){
                unset($course->startdate);
            }

            if(!$this->config->endDate){
                unset($course->endDate);
            }

            if(!$this->config->migrationDate){
                unset($course->migrationdate);
            }

            if(!$this->config->publicationDate){
                unset($course->publicationdate);
                unset($course->publicationdateorigine);
            }

            if(!$this->config->publicationType){
                unset($course->publicationtype);
            }

            if(!$this->config->pageCount){
                unset($course->pagecount);
            }

            if(!$this->config->formateurCount){
                unset($course->formateurcount);
            }

            if(!$this->config->participantCount){
                unset($course->participantcount);
            }

            if(!$this->config->comment){
                unset($course->comment);
            }

            if(!$this->config->migrationstatus){
                unset($course->migrationstatus);
            }

            if(!$this->config->courseversion){
                unset($course->version);
            }

            if(!$this->config->originaca){
                unset($course->originaca);
            }

            if(!$this->config->idnumber){
                unset($course->idnumber);
            }

            if(!$this->config->responsible){
                unset($course->responsible);
            }

            if(!$this->config->publisher){
                unset($course->publisher);
            }
            
            if(!$this->config->trainer) {
                unset($course->formateurs);
            }

            foreach($course as $key => $value){
                if($course->{$key} === null || empty($course->{$key})){
                    $course->{$key} = '-';
                }
            }
        }
    }

    public function getJtableColumn()
    {
        $columns = array(
            'localid' => array(
                'title' => get_string('cfg:idlabel', 'local_supervision_tool'),
                'key' => true,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ),
            'hubid' => array(
                'title' => get_string('cfg:publicationidlabel', 'local_supervision_tool'),
                'create' => false,
                'edit' => false,
                'list' => false,
            ),
            'fullname' => array(
                'title' => get_string('cfg:coursetitlelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ),
            'category' => array(
                'title' => get_string('cfg:categorylabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            ),
            'format' => array(
                'title' => get_string('cfg:coursetypelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            ),
        );

        if($this->config->migrationstatus){
            $columns['migrationstatus'] = array(
                'title' => get_string('cfg:migrationstatuslabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => true,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->depth){
            $columns['depth'] = array(
                'title' => get_string('cfg:depthlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            );
        }

        if($this->config->lastAccess){
            $columns['lastaccess'] = array(
                'title' => get_string('cfg:lastaccesslabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            );
        }

        if($this->config->startDate){
            $columns['startdate'] = array(
                'title' => get_string('cfg:startdatelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            );
        }

        if($this->config->endDate){
            $columns['enddate'] = array(
                'title' => get_string('cfg:enddatelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            );
        }

        if($this->config->migrationDate){
            $columns['migrationdate'] = array(
                'title' => get_string('cfg:migdatelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->publicationDate){
            $columns['publicationdateorigine'] = array(
                'title' => get_string('cfg:publicationdateoriginelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            );

            $columns['publicationdate'] = array(
                'title' => get_string('cfg:publicationdatelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            );
        }

        if($this->config->publicationType){
            $columns['publicationtype'] = array(
                'title' => get_string('cfg:publicationmodelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->pageCount){
            $columns['pagecount'] = array(
                'title' => get_string('cfg:pagecountlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->formateurCount){
            $columns['formateurcount'] = array(
                'title' => get_string('cfg:formateurcountlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->participantCount){
            $columns['participantcount'] = array(
                'title' => get_string('cfg:participantcountlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->comment){
            $columns['comment'] = array(
                'title' => get_string('cfg:commentlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => true,
                'list' => true,
                'listClass' => 'commentarea',
                'sorting' => false
            );
        }

        if($this->config->originaca){
            $columns['originaca'] = array(
                'title' => get_string('cfg:originacalabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'list' => true,
            );
        }

        if($this->config->courseversion){
            $columns['version'] = array(
                'title' => get_string('cfg:courseversionlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false
            );
        }

        if($this->config->originaca){
            $columns['originaca'] = array(
                'title' => get_string('cfg:originacalabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->idnumber){
            $columns['idnumber'] = array(
                'title' => get_string('cfg:idnumberlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false
            );
        }

        if($this->config->responsible){
            $columns['responsible'] = array(
                'title' => get_string('cfg:responsiblelabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'list' => true,
                'sorting' => true
            );
        }

        if($this->config->publisher){
            $columns['publisher'] = array(
                'title' => get_string('cfg:publisherlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'list' => true,
                'sorting' => true
            );
        }
        
        if ($this->config->trainer) {
            $columns['formateurs'] = array(
                'title' => get_string('cfg:trainerlabel', 'local_supervision_tool'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            );
        }

        return $columns;
    }

    public function getMigrationLabelData($migrationstatus, $migrationdate, $validated){
        $migrationlabel = null;
        $migrationdata = null;

        if($migrationstatus === null){
            $migrationstatus = '-';
        }else if($migrationstatus == BaseConvertor::CONV_FAILED){
            $migrationlabel = 'failedstatuslabel';
        }else if($migrationstatus == BaseConvertor::CONV_PROGRESS){
            $migrationlabel = 'progressstatuslabel';
        }else if($migrationstatus == BaseConvertor::CONV_INIT){
            $migrationlabel = 'plannedstatuslabel';
        }else if($migrationstatus == BaseConvertor::CONV_FAILED_QUIZ){
            $migrationlabel = 'failedstatusotherlabel';
        }else {
            if($validated == migrationValidation::NONE && $migrationdate){
                if($migrationstatus == BaseConvertor::CONV_FINISHED){
                    $duration = (($migrationdate + 24*3600*14) - time()) / (24*3600);
                    $migrationdata = intval($duration);
                    $migrationlabel = 'validationlabel';
                }
            }else if($validated == migrationValidation::OK){
                $migrationlabel = 'validatedstatuslabel';
            }else if($validated == migrationValidation::PENDING){
                $migrationlabel = 'validatedstatuspendinglabel';
            }
        }

        return array($migrationlabel, $migrationdata);
    }
}