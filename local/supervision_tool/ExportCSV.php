<?php

require_once($CFG->dirroot.'/local/supervision_tool/FilterResults.php');
require_once($CFG->dirroot.'/local/supervision_tool/FilterDisplay.php');

class ExportCSV
{
    public function __construct()
    {

    }

    public function export($filterConfig, $filterParams, $writeFile = false)
    {
        global $CFG;

        $filterDisplay = new FilterDisplay($filterConfig);

        $filterResult = new FilterResults($filterParams, $filterConfig);

        $data = $filterResult->get_courses();

        $headers = $this->get_headers($filterConfig);
        $file = implode(';', $headers)."\n";

        $filterDisplay->formatData($data);

        $jtcolumns = $filterDisplay->getJtableColumn();

        foreach($data as $d){
            $line = [];
            foreach($jtcolumns as $colname => $conf){
                if(isset($d->{$colname})){

                    $line[] = str_replace('"', '\"', strip_tags($d->{$colname}));
                }
            }

            $file .= '"'.implode('";"', $line).'"'."\n";
        }

        if($writeFile){
            $tmpfileid = time().rand(1,99999);
            $tmpfile = 'csv_'.$tmpfileid.'.csv';

            $path = $CFG->tempdir.'/'.$tmpfile;
            $file = mb_convert_encoding($file, 'UTF-16LE', 'UTF-8');
            file_put_contents($path, $file);

            return $tmpfileid;
        }


        return $file;
    }

    public function get_headers($filterConfig)
    {
        $header = [];

        $header[] = get_string('cfg:idlabel', 'local_supervision_tool');
        $header[] = get_string('cfg:publicationidlabel', 'local_supervision_tool');
        $header[] = get_string('cfg:coursetitlelabel', 'local_supervision_tool');
        $header[] = get_string('cfg:categorylabel', 'local_supervision_tool');
        $header[] = get_string('cfg:coursetypelabel', 'local_supervision_tool');
        
        
        if($filterConfig->migrationstatus){
            $header[] = get_string('cfg:migrationstatuslabel', 'local_supervision_tool');
        }
        
        if($filterConfig->depth){
            $header[] = get_string('cfg:depthlabel', 'local_supervision_tool');
        }
        
        if($filterConfig->lastAccess){
            $header[] = get_string('cfg:lastaccesslabel', 'local_supervision_tool');
        }
        
        if($filterConfig->startDate){
            $header[] = get_string('cfg:startdatelabel', 'local_supervision_tool');
        }
        
        if($filterConfig->endDate){
            $header[] = get_string('cfg:enddatelabel', 'local_supervision_tool');
        }
        
        if($filterConfig->migrationDate){
            $header[] = get_string('cfg:migdatelabel', 'local_supervision_tool');
        }
        
        if($filterConfig->publicationDate){
            $header[] = get_string('cfg:publicationdateoriginelabel', 'local_supervision_tool');
            $header[] = get_string('cfg:publicationdatelabel', 'local_supervision_tool');
        }
        
        if($filterConfig->publicationType){
            $header[] = get_string('cfg:publicationmodelabel', 'local_supervision_tool');
        }
        
        if($filterConfig->pageCount){
            $header[] = get_string('cfg:pagecountlabel', 'local_supervision_tool');
        }
        
        if($filterConfig->formateurCount){
            $header[] = get_string('cfg:formateurcountlabel', 'local_supervision_tool');
        }
        
        if($filterConfig->participantCount){
            $header[] = get_string('cfg:participantcountlabel', 'local_supervision_tool');
        }
        
        if($filterConfig->comment){
            $header[] = get_string('cfg:commentlabel', 'local_supervision_tool');
        }

        if($filterConfig->originaca){
            $header[] = get_string('cfg:originacalabel', 'local_supervision_tool');
        }

        if($filterConfig->courseversion){
            $header[] = get_string('cfg:courseversionlabel', 'local_supervision_tool');
        }

        if($filterConfig->idnumber){
            $header[] = get_string('cfg:idnumberlabel', 'local_supervision_tool');
        }

        if($filterConfig->responsible){
            $header[] = get_string('cfg:responsiblelabel', 'local_supervision_tool');
        }

        if($filterConfig->publisher){
            $header[] = get_string('cfg:publisherlabel', 'local_supervision_tool');
        }

        return $header;
    }
}
