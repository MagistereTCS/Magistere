<?php

require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

define('TASK_OK',0);
define('TASK_WARNING',1);
define('TASK_FAILED',2);
define('TASK_UNKNOWN',3);
define('TASK_DISABLED',4);

class MagistereMonitoring
{
    private $pluginname = 'magisterelib';
    private $monitoring_tasks;
    private $monitoring_tasks_byids;
    private $logs;
    
	
	function __construct()
	{
	    $this->monitoring_tasks = array();
	    $this->monitoring_tasks_byids = array();
	    $this->loadMonitoredTasks();
	}
	
	function execute()
	{
		$this->monitorTasks();
	}
	
	private function d($msg)
	{
		echo date('Y-m-y_H:i:s::').$msg."\n";
	}
	
	private function d2($msg)
	{
		//echo date('Y-m-y_H:i:s::').'####'.print_r($msg,true)."####\n";
	}
	
	private function get_config($name)
	{
	    return get_centralized_db_connection()->get_field('config', 'value', array('plugin'=>$this->pluginname,'name'=>$name));
	}
	
	private function set_config($name,$value)
	{
	    $config = get_centralized_db_connection()->get_record('config', array('plugin'=>$this->pluginname,'name'=>$name));
	    if ($config !== false) {
	        $config->value = $value;
	        get_centralized_db_connection()->update_record('config',$config);
	    }else{
	        $config = new stdClass();
	        $config->plugin = $this->pluginname;
	        $config->name = $name;
	        $config->value = $value;
	        get_centralized_db_connection()->insert_record('config',$config);
	    }
	}
	
	public function getMonitoredTasks()
	{
	    return $this->monitoring_tasks;
	}
	
	public function setTaskIgnore($taskid, $ignore)
	{
	    $this->monitoring_tasks_byids[$taskid]->ignoretask = $ignore;
	    get_centralized_db_connection()->update_record('monitoring_tasks', $this->monitoring_tasks_byids[$taskid]);
	}
	
	private function loadMonitoredTasks()
	{
	    $this->monitoring_tasks = array();
	    $this->monitoring_tasks_byids = get_centralized_db_connection()->get_records('monitoring_tasks');
	    
	    foreach ($this->monitoring_tasks_byids AS $monitoring_task)
	    {
	        $this->monitoring_tasks[$monitoring_task->academy.'->'.$monitoring_task->classname] = $monitoring_task;
	    }
	}
	
	private function insertNewMonitoredTask($academy,$classname)
	{
	    if (!isset($this->monitoring_tasks[$academy.'->'.$classname]))
	    {
	        $task = new stdClass();
	        $task->academy = $academy;
	        $task->classname = $classname;
	        $task->status = 0;
	        $task->faildelay = 0;
	        $task->nextrun = 0;
	        $task->lastupdate = time();
	        $task->ignoretask = 0;
	        get_centralized_db_connection()->insert_record('monitoring_tasks', $task);
	        
	        $this->loadMonitoredTasks();
	        return true;
	    }
	    return false;
	}
	
	private function getMonitoredTask($academy,$classname)
	{
	    if (!isset($this->monitoring_tasks[$academy.'->'.$classname])) {
	        $this->insertNewMonitoredTask($academy,$classname);
	    }
	    
	    return $this->monitoring_tasks[$academy.'->'.$classname];
	}
	
	private function setMonitoredTaskFailDelay($academy,$classname,$faildelay)
	{
	    if (!isset($this->monitoring_tasks[$academy.'->'.$classname])) {
	        $this->insertNewMonitoredTask($academy,$classname);
	    }
	    
	    $this->monitoring_tasks[$academy.'->'.$classname]->faildelay = $faildelay;
	    $this->monitoring_tasks[$academy.'->'.$classname]->lastupdate = time();
	    
	    get_centralized_db_connection()->update_record('monitoring_tasks', $this->monitoring_tasks[$academy.'->'.$classname]);
	}
	
	private function setMonitoredTaskNextRun($academy,$classname,$nextrun)
	{
	    if (!isset($this->monitoring_tasks[$academy.'->'.$classname])) {
	        $this->insertNewMonitoredTask($academy,$classname);
	    }
	    
	    $this->monitoring_tasks[$academy.'->'.$classname]->nextrun = $nextrun;
	    $this->monitoring_tasks[$academy.'->'.$classname]->lastupdate = time();
	    
	    get_centralized_db_connection()->update_record('monitoring_tasks', $this->monitoring_tasks[$academy.'->'.$classname]);
	}
	
	private function setMonitoredTaskStatus($academy,$classname,$status)
	{
	    if (!isset($this->monitoring_tasks[$academy.'->'.$classname])) {
	        $this->insertNewMonitoredTask($academy,$classname);
	    }
	    
	    $this->monitoring_tasks[$academy.'->'.$classname]->status = $status;
	    $this->monitoring_tasks[$academy.'->'.$classname]->lastupdate = time();
	    
	    get_centralized_db_connection()->update_record('monitoring_tasks', $this->monitoring_tasks[$academy.'->'.$classname]);
	}
	
	function monitorTasks()
	{
		global $DB, $CFG;
	    $acas = get_magistere_academy_config();
	    
	    $this->logs = '';
	    $error = false;
	    
	    foreach($acas AS $acaname => $aca)
	    {
	       try
	       {
	           if (databaseConnection::instance()->get($acaname) === false){ $this->logs .= date('Y-m-d_H:i:s::').$acaname.'=>Erreur connexion "'.$acaname.'"'."\n"; return 2;}else{$this->logs .= date('Y-m-y_H:i:s::').$acaname.'=>Connexion reussie "'.$acaname.'"'."\n";}
    		
	           $tasks = $DB->get_records('task_scheduled',array('disabled'=>0));
	           
	           foreach($tasks AS $task)
	           {
	               $monitoredTask = $this->getMonitoredTask($acaname,$task->classname);
	               
	               if ($monitoredTask->ignoretask == 1)
	               {
	                   continue;
	               }
	               
	               if ($task->faildelay > 0)
	               {
	                   $error = true;
	                   $this->setMonitoredTaskFailDelay($acaname, $task->classname, $task->faildelay);
	                   $this->logs .= date('Y-m-d_H:i:s::').$acaname.'=>'.$task->classname.'=> Has a fail delay of '.$task->faildelay.' seconds'."\n";
	                   
	                   $this->setMonitoredTaskStatus($acaname, $task->classname, TASK_FAILED);
	               }
	               else if ($task->nextruntime < time()-7200)
	               {
	                   $error = true;
	                   $this->logs .= date('Y-m-d_H:i:s::').$acaname.'=>'.$task->classname.'=> Should have run hours ago. The next runtime is "'.date('Y-m-d H:i:s',$task->nextruntime).'"'."\n";
	                   
	                   if ($task->nextruntime < time()-43200)
	                   {
	                       $this->setMonitoredTaskStatus($acaname, $task->classname, TASK_FAILED);
	                   }else{
	                       $this->setMonitoredTaskStatus($acaname, $task->classname, TASK_WARNING);
	                   }
	               }else{
	                   $this->setMonitoredTaskStatus($acaname, $task->classname, TASK_OK);
	               }
	               
	           }
    		   
    		
	       } catch (Exception $e) {
	           $this->logs .= date('Y-m-d_H:i:s::').$acaname.'=>EXCEPTION catched : ########'.print_r($e,true).'########\n\n\n';
	           $error = true;
	       }
		
	    }

	    $montasklastmailsend = $this->get_config('montasklastmailsend');
	    $montaskmailedelay = $this->get_config('montaskmailedelay');
	    print_r($this->logs);
	    if ($error && $montasklastmailsend + $montaskmailedelay < time())
	    {
	       $this->send_notification();
	       $this->set_config('montasklastmailsend',time());
	    }
		
	}
	
	
	private function send_notification()
	{
	    global $CFG;
	    
	    // Send notification
	    
	    
	    $plateforme = 'UNKNOWN';
	    if ( strpos($CFG->magistere_domaine,'magistere.education.fr') )
	    {
	        $plateforme = 'PR';
	    }
	    else if ( strpos($CFG->magistere_domaine,'pp-magistere.foad.hp.in.phm.education.gouv.fr') )
	    {
	        $plateforme = 'PP';
	    }
	    else if ( strpos($CFG->magistere_domaine,'qp-magistere.foad.hp.in.phm.education.gouv.fr') )
	    {
	        $plateforme = 'QP';
	    }
	    else if ( strpos($CFG->magistere_domaine,'vs.magistere.fr') )
	    {
	        $plateforme = 'DEV_VS';
	    }
	    else if ( strpos($CFG->magistere_domaine,'jb.magistere.fr') )
	    {
	        $plateforme = 'DEV_JB';
	    }
	    else if ( strpos($CFG->magistere_domaine,'nn.magistere.fr') )
	    {
	        $plateforme = 'DEV_NN';
	    }
	    else if ( strpos($CFG->magistere_domaine,'dev.magistere.fr') )
	    {
	        $plateforme = 'DEV_DEV';
	    }
	    else if ( strpos($CFG->magistere_domaine,'magistere2.sevaille.fr') )
	    {
	        $plateforme = 'DEV_OLD';
	    }
	    
	    $subject = '[M@gistere]['.$plateforme.'][Monitoring][task] Errors found in M@gistere tasks';
	    
	    $messagetext = "Problems found in the M@gistere tasks\n\n".$this->logs;
	    $messagehtml = "Problems found in the M@gistere tasks<br>\n<br>\n".nl2br($this->logs);
	    
	    $fromuser = new stdClass();
	    $fromuser->id = 999999999;
	    $fromuser->email = str_replace('https://', 'no-reply@', $CFG->magistere_domaine);
	    $fromuser->deleted = 0;
	    $fromuser->auth = 'manual';
	    $fromuser->suspended = 0;
	    $fromuser->mailformat = 1;
	    $fromuser->maildisplay = 1;
	    
	    $receivers = array("valentin.sevaille@tcs.com");
	    //$receivers = array("valentin.sevaille@tcs.com", "quentin.gourbeault@tcs.com", "jeanbaptiste.lefevre@tcs.com", "nicolas.nenon@tcs.com");
	    foreach ($receivers as $val) {
	        $userto = new stdClass();
	        $userto->id = -99;
	        $userto->email = $val;
	        $userto->deleted = 0;
	        $userto->auth = 'manual';
	        $userto->suspended = 0;
	        $userto->mailformat = 1;
	        $userto->maildisplay = 1;
	        
	        email_to_user($userto, $fromuser, $subject, $messagetext, $messagehtml);
	        //$this->l('Sending failed notification to user '.$val.'.');
	    }
	}
	
	
	
}
