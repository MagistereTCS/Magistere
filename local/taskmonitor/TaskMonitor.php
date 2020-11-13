<?php
/**
 * 
 *
 * @package    local
 * @subpackage taskmonitor
 * @author     TCS
 * @date       2020
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/taskmonitor/TaskMonitorForm.php');

class TaskMonitor
{
    
    const VIEW_ALL=1;
    const VIEW_PLATFORM=2;
    const VIEW_TASK=3;
    const VIEW_TASKERROR=4;
    
    const VIEWS=array(VIEW_ALL,VIEW_PLATFORM,VIEW_TASK,VIEW_TASKERROR);
    
    const PRIORITY_CRITICAL=4;
    const PRIORITY_HIGH=3;
    const PRIORITY_MEDIUM=2;
    const PRIORITY_LOW=1;
    const PRIORITY_ACTIVE=10;
    const PRIORITY_ALL=11;
    
    const RUNTIME_THRESHOLD=3600;
    
    function __construct(){
        
    }
    
    
    static function getHTML(){
        
        $f = new TaskMonitorForm();
        $f->display();
        
    }
    
    static function getFailedTasks(){
        global $DB, $CFG;
        if (isset($CFG->academie_name) && $CFG->academie_name == 'dgesco'){
            
            require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
            
            $acas = array_keys(get_magistere_academy_config());
            sort($acas);
            
            $failedtasks = array();
            foreach($acas AS $aca)
            {
                if($aca == 'hub' || $aca == 'frontal'){continue;}
                
                $failedtask = databaseConnection::instance()->get($aca)->get_record_sql("SELECT (SELECT COUNT(*) FROM {task_scheduled} WHERE disabled=0 AND faildelay>0) as failed, (SELECT COUNT(*) FROM {task_scheduled} WHERE disabled=0 AND nextruntime<?) as delay", array(time()-3600));
                $failedtask->aca = $aca;
                $failedtasks[] = $failedtask;
            }
            
            return $failedtasks;
            
        }else{
            
            $task = $DB->get_record_sql("SELECT (SELECT COUNT(*) FROM {task_scheduled} WHERE disabled=0 AND faildelay>0) as failed, (SELECT COUNT(*) FROM {task_scheduled} WHERE disabled=0 AND nextruntime<?) as delay", array(time()-3600));
            if (isset($CFG->academie_name)){
                $task->aca = ucfirst(str_replace('ac-', '', $CFG->academie_name));
            }else{
                $course = get_course(1);
                $task->aca = $course->name;
            }
            return array($task);
        }
    }
    
    
    static function jtableGetAllTasks($priority,$sorting,$startindex,$pagesize){
        require_once($GLOBALS['CFG']->dirroot.'/local/taskmonitor/CronTranslator.php');
        if(!in_array($sorting,array('classname ASC','classname DESC','component ASC','component DESC','server ASC','server DESC','status ASC','status DESC'))){
            $sorting = 'classname ASC';
        }
        $sorting = (strpos($sorting,'classname')!==false?'ts.'.$sorting:$sorting);
        
        list($taskscount,$tasks) = self::getLocalTasks(self::VIEW_ALL, $priority, $sorting, $startindex, $pagesize);
        
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['showplatform'] = false;
        $jTableResult['TotalRecordCount'] = $taskscount;
        $jTableResult['Records'] = $tasks;
        
        return json_encode($jTableResult);
    }
    
    static function jtableGetPlateformTasks($priority,$platform,$sorting,$startindex,$pagesize){
        if(!array_key_exists($platform,get_magistere_academy_config())){
            $jTableResult = array();
            $jTableResult['Result'] = "OK";
            $jTableResult['showplatform'] = false;
            $jTableResult['TotalRecordCount'] = 0;
            $jTableResult['Records'] = array();
            return json_encode($jTableResult);
        }
        
        $sorting = (strpos($sorting,'classname')!==false?'ts.'.$sorting:$sorting);
        
        list($taskscount,$tasks) = self::getLocalTasks(self::VIEW_PLATFORM, $priority, $sorting, $startindex, $pagesize,$platform);
        
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['showplatform'] = false;
        $jTableResult['TotalRecordCount'] = $taskscount;
        $jTableResult['Records'] = $tasks;
        
        return json_encode($jTableResult);
    }
    
    
    static function jtableGetTasks($priority,$tasks,$sorting,$startindex,$pagesize){
        $sorting = (strpos($sorting,'classname')!==false?'ts.'.$sorting:$sorting);
        
        if (count($tasks) == 0){
            $jTableResult = array();
            $jTableResult['Result'] = "OK";
            $jTableResult['showplatform'] = false;
            $jTableResult['TotalRecordCount'] = 0;
            $jTableResult['Records'] = array();
            return json_encode($jTableResult);
        }
        
        list($taskscount,$tasks) = self::getGlobalTasks(self::VIEW_TASK, $priority, $sorting, $startindex, $pagesize,$tasks);
        
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['showplatform'] = true;
        $jTableResult['hidepage'] = false;
        $jTableResult['TotalRecordCount'] = $taskscount;
        $jTableResult['Records'] = $tasks;
        
        return json_encode($jTableResult);
    }
    
    
    static function jtableGetAllACATasks($priority,$tasks,$sorting,$startindex,$pagesize){
        $sorting = (strpos($sorting,'classname')!==false?'ts.'.$sorting:$sorting);
        
        if (count($tasks) == 0){
            $jTableResult = array();
            $jTableResult['Result'] = "OK";
            $jTableResult['showplatform'] = false;
            $jTableResult['TotalRecordCount'] = 0;
            $jTableResult['Records'] = array();
            return json_encode($jTableResult);
        }
        
        list($taskscount,$tasks) = self::getLocalTasks(self::VIEW_TASK, $priority, $sorting, $startindex, $pagesize,$tasks);
        
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['showplatform'] = false;
        $jTableResult['hidepage'] = false;
        $jTableResult['TotalRecordCount'] = $taskscount;
        $jTableResult['Records'] = $tasks;
        
        return json_encode($jTableResult);
    }
    
    
    static function jtableGetAllACATaskError($priority,$sorting,$startindex,$pagesize){
        $sorting = (strpos($sorting,'classname')!==false?'ts.'.$sorting:$sorting);
        
        list($taskscount,$tasks) = self::getGlobalTasks(self::VIEW_TASKERROR, $priority, $sorting, $startindex, $pagesize);
        
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['showplatform'] = true;
        $jTableResult['TotalRecordCount'] = $taskscount;
        $jTableResult['Records'] = $tasks;
        
        return json_encode($jTableResult);
    }
    
    static function jtableGetTaskError($priority,$sorting,$startindex,$pagesize){
        $sorting = (strpos($sorting,'classname')!==false?'ts.'.$sorting:$sorting);
        
        list($taskscount,$tasks) = self::getLocalTasks(self::VIEW_TASKERROR, $priority, $sorting, $startindex, $pagesize);
        
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['showplatform'] = false;
        $jTableResult['TotalRecordCount'] = $taskscount;
        $jTableResult['Records'] = $tasks;
        
        return json_encode($jTableResult);
    }
    
    
    static function formatTask($task){
        require_once($GLOBALS['CFG']->dirroot.'/local/taskmonitor/CronTranslator.php');
        
        $jtask = new stdClass();
        $jtask->classname = '<b>'.$task->name.'</b><br>'.$task->classname;
        $jtask->component = $task->component;
        $jtask->server = $task->server;
        $jtask->type = ($task->type=='scheduled'?'Tâche planifiée':'Tâche Adhoc');
        $jtask->disabled = ($task->disabled?'Désactivée':'Active');
        $jtask->runfrequency = CronTranslator::translate($task->minute.' '.$task->hour.' '.$task->day.' '.$task->month.' '.$task->dayofweek);
        $jtask->lastrun = ($task->lastruntime==0?'Jamais':date('d/m/Y H:i',$task->lastruntime));
        $jtask->status = ($task->faildelay>0?'Echec':($task->lastruntime<time()-self::RUNTIME_THRESHOLD?'En attente':'OK'));
        $jtask->queries = (empty($task->elastquery)?'n/a':($task->elastquery).' requêtes<br>'.floor($task->avgquery).' requêtes en moyenne');
        $jtask->runtime = (empty($task->elastruntime)?'n/a':number_format($task->elastruntime/1000000,6).' secondes<br>'.number_format($task->avgruntime/1000000,6).' secondes en moyenne');
        if (isset($task->aca) && $task->aca != null){
            $jtask->platform = $task->aca;
        }
        return $jtask;
    }
    
    private static function getLocalTasks($view,$priority,$sorting,$startindex,$pagesize,$option=array()){
        global $DB;
        $lastweek=time()-604800;
        $where = '';
        $params=array(
            time()-self::RUNTIME_THRESHOLD,
            $lastweek,
            $lastweek
        );
        
        if ($view==self::VIEW_TASKERROR){
            $where = 'WHERE (ts.faildelay>0 OR ts.nextruntime<?) AND ts.disabled=0';
            $params[] = time()-self::RUNTIME_THRESHOLD;
        }else if($view==self::VIEW_TASK){
            if (is_array($option) && count($option) == 0){
                return false;
            }
            $wherein = array();
            foreach($option AS $task){
                $params[] = $task;
                $wherein[] = '?';
            }
            
            $where = "WHERE ts.classname IN(".implode(',', $wherein).")";
        }else if ($view==self::VIEW_PLATFORM){
            if (!array_key_exists($option, get_magistere_academy_config())){
                return false;
            }
        }
        
        $where2 = '';
        if ($priority == TaskMonitor::PRIORITY_CRITICAL || $priority == TaskMonitor::PRIORITY_HIGH){
            $where2 = 'lt.priority = '.$priority;
        }else if ($priority == TaskMonitor::PRIORITY_ACTIVE){
            $where2 = 'ts.disabled = 0';
        }
        
        if (!empty($where2)){
            $where = (empty($where)?'WHERE '.$where2:$where.' AND '.$where2);
        }
        
        $sql = "SELECT ts.id AS uid, ts.component, ts.classname, ts.lastruntime, ts.nextruntime, ts.blocking, ts.minute, ts.hour, ts.day, ts.month, ts.dayofweek, ts.faildelay, ts.disabled, lt.type, lt.name, lt.server, lt.priority,
IF(ts.faildelay>0,'2',IF(ts.nextruntime<?,'1','0')) AS status,
(SELECT query FROM {local_taskmonitor_event} lte1 WHERE lte1.classname=ts.classname ORDER BY starttime DESC LIMIT 1) AS elastquery,
(SELECT runtime FROM {local_taskmonitor_event} lte2 WHERE lte2.classname=ts.classname ORDER BY starttime DESC LIMIT 1) AS elastruntime,
(SELECT AVG(query) FROM {local_taskmonitor_event} lte3 WHERE lte3.classname=ts.classname AND starttime > ?) AS avgquery,
(SELECT AVG(runtime) FROM {local_taskmonitor_event} lte4 WHERE lte4.classname=ts.classname AND starttime > ?) AS avgruntime
FROM {task_scheduled} ts
LEFT JOIN {local_taskmonitor} lt ON (lt.classname=ts.classname)
".$where."
ORDER BY ".$sorting."
LIMIT ".$startindex.",".$pagesize;
        
        $sqlcount = "SELECT COUNT(*) AS cnt
FROM {task_scheduled} ts
LEFT JOIN {local_taskmonitor} lt ON (lt.classname=ts.classname)
".$where;
        
        if ($view==self::VIEW_PLATFORM){
            require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
            $tasks = databaseConnection::instance()->get($option)->get_records_sql($sql,$params);
            $taskscount = databaseConnection::instance()->get($option)->get_record_sql($sqlcount,$params);
        }else{
            $tasks = $DB->get_records_sql($sql,$params);
            $taskscount = $DB->get_record_sql($sqlcount,$params);
        }
        $formatedTasks = array();
        foreach ($tasks AS $task){
            $formatedTasks[] = self::formatTask($task);
        }
        
        return array($taskscount->cnt,$formatedTasks);
    }
    
    
    private static function getGlobalTasks($view,$priority,$sorting,$startindex,$pagesize,$option=null){
        if (!in_array($view, array(self::VIEW_TASK,self::VIEW_TASKERROR))){
            return false;
        }
        $lastweek=time()-604800;
        $where = '';
        $params=array(
            time()-self::RUNTIME_THRESHOLD,
            $lastweek,
            $lastweek
        );
        
        if ($view==self::VIEW_TASKERROR){
            $where = 'WHERE (ts.faildelay>0 OR ts.nextruntime<?) AND ts.disabled=0';
            $params[] = time()-self::RUNTIME_THRESHOLD;
        }else if($view==self::VIEW_TASK){
            if (is_array($option) && count($option) == 0){
                return false;
            }
            $wherein = array();
            foreach($option AS $task){
                $params[] = $task;
                $wherein[] = '?';
            }
            
            $where = "WHERE ts.classname IN(".implode(',', $wherein).")";
        }
        
        $where2 = '';
        if ($priority == TaskMonitor::PRIORITY_CRITICAL || $priority == TaskMonitor::PRIORITY_HIGH){
            $where2 = 'lt.priority = '.$priority;
        }else if ($priority == TaskMonitor::PRIORITY_ACTIVE){
            $where2 = 'ts.disabled = 0';
        }
        
        if (!empty($where2)){
            $where = (empty($where)?'WHERE '.$where2:$where.' AND '.$where2);
        }
        
        $acas = array_keys(get_magistere_academy_config());
        sort($acas);
        
        $orderby='';
        if(strpos($sorting,'platform')!==false){
            if(strpos($sorting, 'DESC')){
                rsort($acas);
            }
        }else{
            $orderby=' ORDER BY '.$sorting;
        }
        
        
        $sql = "SELECT ts.id AS uid, ts.component, ts.classname, ts.lastruntime, ts.nextruntime, ts.blocking, ts.minute, ts.hour, ts.day, ts.month, ts.dayofweek, ts.faildelay, ts.disabled, lt.type, lt.name, lt.server, lt.priority,
IF(ts.faildelay>0,'2',IF(ts.nextruntime<?,'1','0')) AS status,
(SELECT query FROM {local_taskmonitor_event} lte1 WHERE lte1.classname=ts.classname ORDER BY starttime DESC LIMIT 1) AS elastquery,
(SELECT runtime FROM {local_taskmonitor_event} lte2 WHERE lte2.classname=ts.classname ORDER BY starttime DESC LIMIT 1) AS elastruntime,
(SELECT AVG(query) FROM {local_taskmonitor_event} lte3 WHERE lte3.classname=ts.classname AND starttime > ?) AS avgquery,
(SELECT AVG(runtime) FROM {local_taskmonitor_event} lte4 WHERE lte4.classname=ts.classname AND starttime > ?) AS avgruntime
FROM {task_scheduled} ts
LEFT JOIN {local_taskmonitor} lt ON (lt.classname=ts.classname)
".$where.$orderby;
        
        $sortfield = '';
        $sortasc=!strpos($sorting,'DESC');
        if (strpos($sorting,'classname')!==false){
            $sortfield = 'classname';
        }else if(strpos($sorting,'component')!==false){
            $sortfield = 'component';
        }else if (strpos($sorting,'server')!==false){
            $sortfield = 'server';
        }else if (strpos($sorting,'type')!==false){
            $sortfield = 'type';
        }else if(strpos($sorting,'status')!==false){
            $sortfield = 'status';
        }
        
        $acatasks = array();
        foreach($acas AS $aca)
        {
            if($aca == 'hub' || $aca == 'frontal'){
                continue;
            }
            
            $tasks = databaseConnection::instance()->get($aca)->get_records_sql($sql,$params);
            foreach ($tasks AS $task){
                //echo '####'.$sortfield.'===>>'.$task->{$sortfield}.'####';
                $task->aca = $aca;
                if (!empty($sortfield)){
                    $acatasks[$task->{$sortfield}.'_'.$aca.$task->uid] = $task;
                }else{
                    $acatasks[] = $task;
                }
            }
        }
        
        if (!empty($sortfield)){
            if ($sortasc){
                ksort($acatasks);
            }else{
                krsort($acatasks);
            }
        }
        
        $rawtasks = array_slice($acatasks, $startindex, $pagesize);
        $formatedTasks = array();
        foreach ($rawtasks AS $rawtask){
            $formatedTasks[] = self::formatTask($rawtask);
        }
        
        return array(count($acatasks),$formatedTasks);
    }
    
}







