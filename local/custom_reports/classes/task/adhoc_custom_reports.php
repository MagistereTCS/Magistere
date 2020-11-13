<?php

namespace local_custom_reports\task;

require_once($CFG->dirroot.'/local/custom_reports/lib.php');

class adhoc_custom_reports extends \core\task\adhoc_task {

    private function l($str) {
        echo "custom_reports : ".$str;
    }

    public function execute() {
        $time = time();
         try {
             // Get the custom data.
             $data = $this->get_custom_data();
             $this->l('Started adhoc task execution at '.date('d/m/Y H:i:s', $time).'with userid : '.$data->user_id);
             $records = launch_stat_query($data);

             $basepath = null;
             if (count($records) > 0) {
                 $basepath = getBaseExportFilepath($time, $data->user_id);
                 $filepath = get_stats_filepath($basepath, $data->export_type);
                 export_stats_to_file($records, $data->export_type, $filepath);
             }

             send_export_by_mail($data->user_id, $data->timecreated, $filepath);

         } catch (\Exception $e){
             $this->l('Adhoc task execution failed with error : '.$e->getMessage());
             $this->l($e->getTraceAsString());
             // if it failed, still send mail but with the error and stacktrace
//             send_export_error_mail($data->user_id);
         }
    }
}


















