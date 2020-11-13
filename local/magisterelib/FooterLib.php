<?php


class FooterLib
{
    const PAGE_TERMS = 1;
    const PAGE_ABOUT = 2;
    const PAGE_CONTACT = 3;
    const PAGE_VIRTUALCLASS = 4;
    const PAGE_CONHELP = 5;
    const PAGE_INSTANCE = 6;
    const PAGE_HELP = 7;
    
    public static function get_page_url($type)
    {
        global $CFG,$DB;
        
        $frontal_value = '';
        // selection du type de formulaire 
        if(isset($type)){
            if ($type == self::PAGE_TERMS){
        		$value = 'terms';
        		$frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2232';
            }elseif ($type == self::PAGE_ABOUT){
        		$value = 'about';
        		$frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2234';
            }elseif ($type == self::PAGE_CONTACT){
        		$value = 'contact';
        		$frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2235';
            }elseif ($type == self::PAGE_VIRTUALCLASS){
        		$value = 'virtualclass';
            }elseif ($type == self::PAGE_CONHELP){
        	    $frontal_value = $CFG->magistere_domaine.'/dgesco/mod/page/view.php?id=2236';
        		$value = 'conhelp';
            }elseif ($type == self::PAGE_INSTANCE){
                $value = 'instance';
            }elseif ($type == self::PAGE_HELP){
        		$value = 'help';
            }else{
        		return 'error';
        	}
        }
        
        $query = "SELECT id FROM {course_modules} WHERE module IN (SELECT id FROM {modules} WHERE name = 'page') AND course = 1 AND instance in (SELECT id FROM {page} WHERE intro LIKE '%".$value."%')";
        
        try
        {
            if (isfrontal())
            {
            	return $frontal_value;
            }
            
            $result = $DB->get_records_sql($query);
            
            if($result)
            {
            	$row = array_shift($result);
            
            	$url_page = $CFG->wwwroot .'/mod/page/view.php?id='.$row->id;
            	return $url_page;
            }
            else{
            	return $CFG->wwwroot;
            }
        
    	} catch(Exception $e){}
    }
}