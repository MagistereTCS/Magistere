<?php
/**
 * 
 *
 * @package    local
 * @subpackage taskmonitor
 * @author     TCS
 * @date       2020
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class TaskMonitorForm extends moodleform {
    
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, array $ajaxformdata = null)
    {
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }
    
    /**
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function definition() {
        global $DB,$CFG;
        $mform =& $this->_form;
        
        $mform->disable_form_change_checker();
        
        $mform->addElement('header', 'failedtasksheader', get_string('warn_tasks', 'local_taskmonitor'));
        $mform->setExpanded('failedtasksheader',false);
        
        $failedTasks = TaskMonitor::getFailedTasks();
        $nbcolum = 3;
        if (count($failedTasks)>1){
            $nbfailed = 0;
            $html = '';
            $i=1;
            foreach($failedTasks AS $failedTask){
                if ($i==1){
                    $html .= '<tr>';
                }
                $html .= '<td class="col1">'.$failedTask->aca.'</td><td class="col2">'.$failedTask->delay.'</td><td class="col3">'.$failedTask->failed.'</td>';
                $nbfailed += $failedTask->failed;
                if ($i==$nbcolum){
                    $html .= '</tr>';
                    $i=0;
                }
                $i++;
            }
            
            if ($i != 1 && $i <= $nbcolum){
                for ($j=$i;$j<=$nbcolum;$j++){
                    $html .= '<td class="lastcol1">&nbsp;</td><td class="lastcol2">&nbsp;</td><td class="lastcol3">&nbsp;</td>';
                }
            }
            $header = '<th class="head1">'.get_string('plateform','local_taskmonitor').'</th><th class="head2"><i class="fas fa-stopwatch" title="Délai d\'execution normal depassé"></i></th><th class="head3"><i class="fas fa-exclamation-triangle" title="Erreurs"></i></th>';
            $mform->addElement('html', '<table id="failedtask"><tr>'.str_repeat($header,$nbcolum).'</tr>'.$html.'</table><br>');
            
            if ($nbfailed>0){
                $mform->setExpanded('failedtasksheader');
            }
        }else{
            $mform->addElement('html', '<table id="failedtask"><tr><th>'.get_string('plateform','local_taskmonitor').'</th><th><i class="fas fa-stopwatch" title="Délai d\'execution normal depassé"></i></th><th><i class="fas fa-exclamation-triangle" title="Erreurs"></i></th></tr>
<tr><td class="col1">'.$failedTasks[0]->aca.'</td><td class="col2">'.$failedTasks[0]->delay.'</td><td class="col3">'.$failedTasks[0]->failed.'</td></tr>
</table><br>');
        }
        
        if(isset($CFG->academie_name) && $CFG->academie_name == 'dgesco'){
            $viewlist = array(
                TaskMonitor::VIEW_PLATFORM=>get_string('filter_view_platform', 'local_taskmonitor'),
                TaskMonitor::VIEW_TASK=>get_string('filter_view_task', 'local_taskmonitor'),
                TaskMonitor::VIEW_TASKERROR=>get_string('filter_view_taskerror', 'local_taskmonitor')
            );
        }else{
            $viewlist = array(
                TaskMonitor::VIEW_ALL=>get_string('filter_view_all', 'local_taskmonitor'),
                TaskMonitor::VIEW_TASK=>get_string('filter_view_task', 'local_taskmonitor'),
                TaskMonitor::VIEW_TASKERROR=>get_string('filter_view_taskerror', 'local_taskmonitor')
            );
        }
        
        $platforms = array();
        foreach (get_magistere_academy_config() as $aca=>$acad){
            if(in_array($aca,array('hub','frontal'))){
                continue;
            }
            $platforms[$aca] = ucwords(str_replace('ac-','',$aca)," \t\r\n\f\v-");
        }
        
        $prioritylist = array(
            TaskMonitor::PRIORITY_CRITICAL=>get_string('priority_critical', 'local_taskmonitor'),
            TaskMonitor::PRIORITY_HIGH    =>get_string('priority_high', 'local_taskmonitor'),
            TaskMonitor::PRIORITY_ACTIVE  =>get_string('priority_active', 'local_taskmonitor'),
            TaskMonitor::PRIORITY_ALL     =>get_string('priority_all', 'local_taskmonitor')
        );
        
        
        $mform->addElement('header', 'filterheader', get_string('filters', 'local_taskmonitor'));
        $mform->setExpanded('filterheader');
        $mform->addElement('select', 'filterPriority', get_string('filter_priority', 'local_taskmonitor'), $prioritylist);
        
        $mform->addElement('select', 'filterView', get_string('filter_view', 'local_taskmonitor'), $viewlist);
        
        $mform->addElement('select', 'filterPlatform', get_string('filter_platform', 'local_taskmonitor'), $platforms);
        
        
        $tasks = $DB->get_records_sql('SELECT id, classname, name FROM {local_taskmonitor}');
        $options =  '';
        foreach($tasks AS $task){
            $options .= '<option value="'.$task->classname.'" data="'.$task->name.'">'.$task->classname.'</option>';
        }
        
        //$mform->addElement('html', '<input type="text" id="tasksearch"/><br/><select id="taskselect" multiple>'.$options.'</select>');
        $mform->addElement('html', '<div id="taskitem" class="fitem" style="display:none"><div class="fitemtitle"><label>Taches</label></div><div class="felement">
<input type="text" id="tasksearch"/><br/><select id="taskselect" multiple>'.$options.'</select>
</div></div>');
        
        $mform->addElement('html', '<div class="fitem"><div class="fitemtitle"></div><div class="felement">
<input type="button" id="filtersubmit" value="Afficher"/>
</div></div>');
        
        
        $mform->addElement('header', 'resultsheader', get_string('results', 'local_taskmonitor'));
        $mform->setExpanded('resultsheader');
        $mform->addElement('html', '<div id="resultTable" style="width:100%"></div>');
        
        
        
        
        
        
        
        
    }
    
    
}
