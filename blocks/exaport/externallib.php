<?php
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/weblib.php");
require_once $CFG->dirroot . '/blocks/exaport/lib/lib.php';
require_once $CFG->dirroot . '/lib/filelib.php';

/**
 * COMPETENCE TYPES
 */
define('TYPE_DESCRIPTOR', 0);
define('TYPE_TOPIC', 1);

class block_exaport_external extends external_api {


	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_items_parameters() {
		return new external_function_parameters(
				array('level' => new external_value(PARAM_INT, 'id of level/parent category'))
		);

	}

	/**
	 * Get items
	 * @param int level
	 * @return array of course subjects
	 */
	public static function get_items($level) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::get_items_parameters(), array('level'=>$level));

		$conditions=array("pid"=>$level,"userid"=>$USER->id);
		$categories = $DB->get_records("block_exaportcate", $conditions);

		$results = array();

		foreach($categories as $category) {
			$result = new stdClass();
			$result->id = $category->id;
			$result->name = $category->name;
			$result->type = "category";
			$result->parent = $category->pid;

			$results[] = $result;
		}

		$items = $DB->get_records("block_exaportitem", array("userid" => $USER->id,"categoryid" => $level),'','id,name,type, 0 as parent');
		$results = array_merge($results,$items);

		return $results;
	}

	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function get_items_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'id' => new external_value(PARAM_INT, 'id of item'),
								'name' => new external_value(PARAM_TEXT, 'title of item'),
								'type' => new external_value(PARAM_TEXT, 'title of item (note,file,link,category)'),
								'parent' => new external_value(PARAM_TEXT, 'iff item is a cat, parent-cat is returned')
						)
				)
		);
	}

	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_item_parameters() {
		return new external_function_parameters(
				array('itemid' => new external_value(PARAM_INT, 'id of item'))
		);

	}

	/**
	 * Get item
	 * @param int itemid
	 * @return array of course subjects
	 */
	public static function get_item($itemid) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::get_item_parameters(), array('itemid'=>$itemid));

		$conditions=array("id"=>$itemid,"userid"=>$USER->id);
		$item = $DB->get_record("block_exaportitem", $conditions, 'id,userid,type,categoryid,name,intro,url',MUST_EXIST);
		$category = $DB->get_field("block_exaportcate","name",array("id"=>$item->categoryid));

		if(!$category)
			$category = "Hauptkategorie";

		$item->category = $category;
		$item->file = "";
		$item->isimage = false;
		$item->filename = "";
		$item->intro = strip_tags($item->intro);

		if ($item->type == 'file') {
			if ($file = block_exaport_get_item_file($item)) {
				$item->file = ("{$CFG->wwwroot}/blocks/exaport/portfoliofile.php?access=portfolio/id/".$USER->id."&itemid=".$item->id);
				$item->isimage = $file->is_valid_image();
				$item->filename = $file->get_filename();
			}
		}
			
		return $item;
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function get_item_returns() {
		return new external_single_structure(
				array(
						'id' => new external_value(PARAM_INT, 'id of item'),
						'name' => new external_value(PARAM_TEXT, 'title of item'),
						'type' => new external_value(PARAM_TEXT, 'type of item (note,file,link,category)'),
						'category' => new external_value(PARAM_TEXT, 'title of category'),
						'url' => new external_value(PARAM_TEXT, 'url'),
						'intro' => new external_value(PARAM_RAW, 'description of item'),
						'filename' => new external_value(PARAM_TEXT, 'title of item'),
						'file' => new external_value(PARAM_URL, 'file url'),
						'isimage' => new external_value(PARAM_BOOL,'true if file is image')
				)
		);
	}

	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function add_item_parameters() {
		return new external_function_parameters(
				array('title' => new external_value(PARAM_TEXT, 'item title'),
						'categoryid' => new external_value(PARAM_INT, 'categoryid'),
						'url' => new external_value(PARAM_URL, 'url'),
						'intro' => new external_value(PARAM_TEXT, 'introduction'),
						'filename' => new external_value(PARAM_TEXT, 'filename, used to look up file and create a new one in the exaport file area'),
						'type' => new external_value(PARAM_TEXT, 'type of item (note,file,link,category)'))
		);

	}

	/**
	 * Add item
	 * @param int itemid
	 * @return array of course subjects
	 */
	public static function add_item($title,$categoryid,$url,$intro,$filename,$type) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::add_item_parameters(), array('title'=>$title,'categoryid'=>$categoryid,'url'=>$url,'intro'=>$intro,'filename'=>$filename,'type'=>$type));

		$itemid = $DB->insert_record("block_exaportitem", array('userid'=>$USER->id,'name'=>$title,'categoryid'=>$categoryid,'url'=>$url,'intro'=>$intro,'type'=>$type,'timemodified'=>time()));

		//if a file is added we need to copy the file from the user/private filearea to block_exaport/item_file with the itemid from above
		if($type == "file") {
			$context = context_user::instance($USER->id);
			$fs = get_file_storage();
			try {
				$old = $fs->get_file($context->id, "user", "private", 0, "/", $filename);

				if($old) {
					$file_record = array('contextid'=>$context->id, 'component'=>'block_exaport', 'filearea'=>'item_file',
							'itemid'=>$itemid, 'filepath'=>'/', 'filename'=>$old->get_filename(),
							'timecreated'=>time(), 'timemodified'=>time());
					$fs->create_file_from_storedfile($file_record, $old->get_id());
				}
			} catch (Exception $e) {
				//some problem with the file occured
			}
		}

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function add_item_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}

	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function update_item_parameters() {
		return new external_function_parameters(
				array(	'id' => new external_value(PARAM_INT, 'item id'),
						'title' => new external_value(PARAM_TEXT, 'item title'),
						'url' => new external_value(PARAM_TEXT, 'url'),
						'intro' => new external_value(PARAM_TEXT, 'introduction'),
						'filename' => new external_value(PARAM_TEXT, 'filename, used to look up file and create a new one in the exaport file area'),
						'type' => new external_value(PARAM_TEXT, 'type of item (note,file,link,category)'))
		);

	}

	/**
	 * Update item
	 * @param int itemid
	 * @return array of course subjects
	 */
	public static function update_item($id, $title,$url,$intro,$filename,$type) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::update_item_parameters(), array('id'=>$id,'title'=>$title,'url'=>$url,'intro'=>$intro,'filename'=>$filename,'type'=>$type));

		$record = new stdClass();
		$record->id = $id;
		$record->name = $title;
		$record->categoryid = $DB->get_field("block_exaportitem", "categoryid", array("id"=>$id));
		$record->url = $url;
		$record->intro = $intro;
		$record->type = $type;

		block_exaport_file_remove($DB->get_record("block_exaportitem",array("id"=>$id)));
		$DB->update_record("block_exaportitem", $record);

		//if a file is added we need to copy the file from the user/private filearea to block_exaport/item_file with the itemid from above
		if($type == "file") {
			$context = context_user::instance($USER->id);
			$fs = get_file_storage();
			try {
				$old = $fs->get_file($context->id, "user", "private", 0, "/", $filename);
					
				if($old) {
					$file_record = array('contextid'=>$context->id, 'component'=>'block_exaport', 'filearea'=>'item_file',
							'itemid'=>$id, 'filepath'=>'/', 'filename'=>$old->get_filename(),
							'timecreated'=>time(), 'timemodified'=>time());
					$fs->create_file_from_storedfile($file_record, $old->get_id());
				}
			} catch (Exception $e) {
			}
		}

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function update_item_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}

	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function delete_item_parameters() {
		return new external_function_parameters(
				array(	'id' => new external_value(PARAM_INT, 'item id'))
		);
	}

	/**
	 * Delete item
	 * @param int itemid
	 * @return array of course subjects
	 */
	public static function delete_item($id) {
		global $CFG,$DB,$USER;
		$params = self::validate_parameters(self::delete_item_parameters(), array('id'=>$id));

		block_exaport_file_remove($DB->get_record("block_exaportitem",array("id"=>$id)));

		$DB->delete_records("block_exaportitem", array('id'=>$id));

		$interaction = block_exaport_check_competence_interaction();
		if ($interaction) {
			$DB->delete_records('block_exacompcompactiv_mm', array("activityid" => $id, "eportfolioitem" => 1));
			$DB->delete_records('block_exacompcompuser_mm', array("activityid" => $id, "eportfolioitem" => 1, "reviewerid" => $USER->id));
		}

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function delete_item_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function list_competencies_parameters() {
		return new external_function_parameters(
				array()
		);

	}

	/**
	 * Get views
	 * @return array of e-Portfolio views
	 */
	public static function list_competencies() {
		global $CFG,$DB,$USER;
	
		$courses = $DB->get_records('course', array());

		$descriptors = array();
		foreach($courses as $course){
			$context = context_course::instance($course->id);
			if(is_enrolled($context, $USER)){
				$query = "SELECT t.id as topdescrid, d.id,d.title,tp.title as topic,tp.id as topicid, s.title as subject,s.id as subjectid,d.niveauid FROM {block_exacompdescriptors} d, {block_exacompcoutopi_mm} c, {block_exacompdescrtopic_mm} t, {block_exacomptopics} tp, {block_exacompsubjects} s
				WHERE d.id=t.descrid AND t.topicid = c.topicid AND t.topicid=tp.id AND tp.subjid = s.id AND c.courseid = ?";

				$query.= " ORDER BY s.title,tp.title,d.sorting";
				$alldescr = $DB->get_records_sql($query, array($course->id));
				if (!$alldescr) {
					$alldescr = array();
				}
				foreach($alldescr as $descr){
					$descriptors[] = $descr;
				}
			}
		}

		$competencies = array();
		foreach ($descriptors as $descriptor){
			if(!array_key_exists ($descriptor->subjectid, $competencies)){
				$competencies[$descriptor->subjectid] = new stdClass();
				$competencies[$descriptor->subjectid]->id = $descriptor->subjectid;
				$competencies[$descriptor->subjectid]->name = $descriptor->subject;
				$competencies[$descriptor->subjectid]->topics = array();
			}

			if(!array_key_exists ($descriptor->topicid, $competencies[$descriptor->subjectid]->topics)){
				$competencies[$descriptor->subjectid]->topics[$descriptor->topicid] = new stdClass();
				$competencies[$descriptor->subjectid]->topics[$descriptor->topicid]->id = $descriptor->topicid;
				$competencies[$descriptor->subjectid]->topics[$descriptor->topicid]->name = $descriptor->topic;
				$competencies[$descriptor->subjectid]->topics[$descriptor->topicid]->descriptors = array();
			}

			$competencies[$descriptor->subjectid]->topics[$descriptor->topicid]->descriptors[$descriptor->id] = new stdClass();
			$competencies[$descriptor->subjectid]->topics[$descriptor->topicid]->descriptors[$descriptor->id]->id = $descriptor->id;
			$competencies[$descriptor->subjectid]->topics[$descriptor->topicid]->descriptors[$descriptor->id]->name = $descriptor->title;
		}

		return $competencies;

	}

	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function list_competencies_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'id' => new external_value(PARAM_INT, 'id of subject'),
								'name' => new external_value(PARAM_TEXT, 'title of subject'),
								'topics' => new external_multiple_structure(
										new external_single_structure(
												array(
														'id' => new external_value(PARAM_INT, 'id of topic'),
														'name' => new external_value(PARAM_TEXT, 'title of topic'),
														'descriptors' => new external_multiple_structure(
																new external_single_structure(
																		array(
																				'id' => new external_value(PARAM_INT, 'id of descriptor'),
																				'name'=> new external_value(PARAM_TEXT, 'name of descriptor')
																		)
																)
														)
												)
										)
								)
						)
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function set_item_competence_parameters() {
		return new external_function_parameters(
				array(	'itemid' => new external_value(PARAM_INT, 'item id'),
						'descriptorid' => new external_value(PARAM_INT, 'descriptor id'),
						'val' => new external_value(PARAM_INT, '1 to assign, 0 to unassign')
				)
		);

	}

	/**
	 * Add a descriptor to an item
	 * @param int itemid, descriptorid, val
	 * @return array of course subjects
	 */
	public static function set_item_competence($itemid,$descriptorid, $val) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::set_item_competence_parameters(), array('itemid'=>$itemid,'descriptorid'=>$descriptorid, 'val'=>$val));

		if($val == 1){
			$item = $DB->get_record("block_exaportitem", array("id"=>$itemid));
			$course = $DB->get_record("course", array("id"=>$item->courseid));
			$DB->insert_record("block_exacompcompactiv_mm", array('compid'=>$descriptorid, 'activityid'=>$itemid, 'eportfolioitem'=>1, 'activitytitle'=>$item->name, 'coursetitle'=>$course->shortname, 'comptype'=>TYPE_DESCRIPTOR));
			$DB->insert_record('block_exacompcompuser_mm', array("compid" => $descriptorid, "activityid" => $itemid, "eportfolioitem" => 1, "reviewerid" => $USER->id, "userid" => $USER->id, "role" => 0, 'comptype'=>TYPE_DESCRIPTOR));
		}else if($val == 0){
			$DB->delete_records("block_exacompcompactiv_mm", array('compid'=>$descriptorid, 'activityid'=>$itemid, 'eportfolioitem'=>1, 'comptype'=>TYPE_DESCRIPTOR));
			$DB->delete_records("block_exacompcompuser_mm", array("compid"=>$descriptorid, 'activityid'=>$itemid, 'eportfolioitem'=>1, 'comptype'=>TYPE_DESCRIPTOR));
		}

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function set_item_competence_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}

	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_views_parameters() {
		return new external_function_parameters(
				array()
		);

	}

	/**
	 * Get views
	 * @return array of e-Portfolio views
	 */
	public static function get_views() {
		global $CFG,$DB,$USER;

		$conditions=array("userid"=>$USER->id);
		$views = $DB->get_records("block_exaportview", $conditions);

		$results = array();

		foreach($views as $view) {
			$result = new stdClass();
			$result->id = $view->id;
			$result->name = $view->name;
			$result->description = $view->description;
			$results[] = $result;
		}

		return $results;
	}

	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function get_views_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'id' => new external_value(PARAM_INT, 'id of view'),
								'name' => new external_value(PARAM_TEXT, 'title of view'),
								'description' => new external_value(PARAM_RAW, 'description of view')
						)
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_view_parameters() {
		return new external_function_parameters(
				array('id' => new external_value(PARAM_INT, 'view id'))
		);
	}

	/**
	 * Get view
	 * @param int id
	 * @return detailed view including list of items
	 */
	public static function get_view($id) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::get_view_parameters(), array('id'=>$id));

		$conditions=array("id"=>$id);
		$view = $DB->get_record("block_exaportview", $conditions);

		$result->id = $view->id;
		$result->name = $view->name;
		$result->description = $view->description;

		$conditions = array("viewid"=>$id);
		$items = $DB->get_records("block_exaportviewblock", $conditions);

		$result->items = array();
		foreach($items as $item) {
			if($item->type == "item"){
				$conditions = array("id"=>$item->itemid);
				$itemdb = $DB->get_record("block_exaportitem", $conditions);
					
				$resultitem = new stdClass();
				$resultitem->id = $itemdb->id;
				$resultitem->name = $itemdb->name;
				$resultitem->type = $itemdb->type;
				$result->items[] = $resultitem;
			}
		}

		return $result;
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function get_view_returns() {
		return new external_single_structure(
				array(
						'id' => new external_value(PARAM_INT, 'id of view'),
						'name' => new external_value(PARAM_TEXT, 'title of view'),
						'description' => new external_value(PARAM_RAW, 'description of view'),
						'items'=> new external_multiple_structure(
								new external_single_structure(
										array(
												'id' => new external_value(PARAM_INT, 'id of item'),
												'name' => new external_value(PARAM_TEXT, 'title of item'),
												'type' => new external_value(PARAM_TEXT, 'title of item (note,file,link,category)')
										)
								)
						)
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function add_view_parameters() {
		return new external_function_parameters(
				array(
						'name' => new external_value(PARAM_TEXT, 'view title'),
						'description' => new external_value(PARAM_TEXT, 'description')
				)
		);
	}

	/**
	 * Add view
	 * @param String name, String description
	 * @return success
	 */
	public static function add_view($name,$description) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::add_view_parameters(), array('name'=>$name,'description'=>$description));

		$viewid = $DB->insert_record("block_exaportview", array('userid'=>$USER->id,'name'=>$name,'description'=>$description, 'timemodified'=>time()));

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function add_view_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}

	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function update_view_parameters() {
		return new external_function_parameters(
				array(
						'id' => new external_value(PARAM_INT, 'view id'),
						'name' => new external_value(PARAM_TEXT, 'view title'),
						'description' => new external_value(PARAM_TEXT, 'description')
				)
		);
	}

	/**
	 * Update view
	 * @param int id, String name, String description
	 * @return success
	 */
	public static function update_view($id, $name,$description) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::update_view_parameters(), array('id'=>$id,'name'=>$name,'description'=>$description));

		$record = new stdClass();
		$record->id = $id;
		$record->name = $name;
		$record->description = $description;
		$DB->update_record("block_exaportview", $record);

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function update_view_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function delete_view_parameters() {
		return new external_function_parameters(
				array(
						'id' => new external_value(PARAM_INT, 'view id')
				)
		);
	}

	/**
	 * Delete view
	 * @param int id
	 * @return success
	 */
	public static function delete_view($id) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::delete_view_parameters(), array('id'=>$id));

		$DB->delete_records("block_exaportview", array("id"=>$id));

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function delete_view_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_all_items_parameters() {
		return new external_function_parameters(
				array(
				)
		);
	}

	/**
	 * Get all items
	 * @return all items available
	 */
	public static function get_all_items() {
		global $CFG,$DB,$USER;

		$categories = $DB->get_records("block_exaportcate", array("userid"=>$USER->id));

		$itemstree = array();
		$maincategory = $DB->get_records("block_exaportitem", array("userid"=>$USER->id, "categoryid"=>0));

		$itemstree[0] = new stdClass();
		$items_temp = array();
		foreach($maincategory as $item){
			$itemstree[0]->id = 0;
			$itemstree[0]->name = "Hauptkategorie";
			$item_temp = new stdClass();
			$item_temp->id = $item->id;
			$item_temp->name = $item->name;
			$items_temp[] = $item_temp;
		}
		$itemstree[0]->items = $items_temp;
		foreach($categories as $category){
			$categoryitems = $DB->get_records("block_exaportitem", array("userid"=>$USER->id, "categoryid"=>$category->id));

			$itemstree[$category->id] = new stdClass();
			$items_temp = array();
			foreach($categoryitems as $item){
				$itemstree[$category->id]->id = $category->id;
				$itemstree[$category->id]->name = $category->name;
				$item_temp = new stdClass();
				$item_temp->id = $item->id;
				$item_temp->name = $item->name;
				$items_temp[] = $item_temp;
			}
			$itemstree[$category->id]->items = $items_temp;
		}

		return $itemstree;
	}

	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function get_all_items_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'id' => new external_value(PARAM_INT, 'id of category'),
								'name' => new external_value(PARAM_TEXT, 'title of category'),
								'items' => new external_multiple_structure(
										new external_single_structure(
												array(
														'id' => new external_value(PARAM_INT, 'id of item'),
														'name' => new external_value(PARAM_TEXT, 'name of item')
												)
										)
								)
						)
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function add_view_item_parameters() {
		return new external_function_parameters(
				array(
						'viewid' => new external_value(PARAM_INT, 'view id'),
						'itemid' => new external_value(PARAM_INT, 'item id')
				)
		);
	}

	/**
	 * Add item to view
	 * @param int viewid, itemid
	 * @return success
	 */
	public static function add_view_item($viewid, $itemid) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::add_view_item_parameters(), array('viewid'=>$viewid, 'itemid'=>$itemid));

		$query = "SELECT MAX(positiony) from {block_exaportviewblock} WHERE viewid=?";
		$max = $DB->get_field_sql($query, array($viewid));
		$ycoord = intval($max)+1;

		$blockid = $DB->insert_record("block_exaportviewblock", array("viewid"=>$viewid, "itemid"=>$itemid, "positionx"=>1, "positiony"=>$ycoord, "type"=>"item"));

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function add_view_item_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function delete_view_item_parameters() {
		return new external_function_parameters(
				array(
						'viewid' => new external_value(PARAM_INT, 'view id'),
						'itemid' => new external_value(PARAM_INT, 'item id')
				)
		);
	}

	/**
	 * Remove item from view
	 * @param int viewid, itemid
	 * @return success
	 */
	public static function delete_view_item($viewid, $itemid) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::delete_view_item_parameters(), array('viewid'=>$viewid, 'itemid'=>$itemid));
		$query = "SELECT MAX(positiony) from {block_exaportviewblock} WHERE viewid=? AND itemid=?";
		$max = $DB->get_field_sql($query, array($viewid, $itemid));
		$ycoord = intval($max);
		$DB->delete_records("block_exaportviewblock", array("viewid"=>$viewid, "itemid"=>$itemid, "positiony"=>$ycoord));

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function delete_view_item_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function view_grant_external_access_parameters() {
		return new external_function_parameters(
				array(
						'id' => new external_value(PARAM_INT, 'view id'),
						'val' => new external_value(PARAM_INT, '1 for check, 0 for uncheck')
				)
		);
	}

	/**
	 * Grant external acces to view
	 * @param int id, val
	 * @return success
	 */
	public static function view_grant_external_access($id, $val) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::view_grant_external_access_parameters(), array('id'=>$id, 'val'=>$val));

		$record = new stdClass();
		$record->id = $id;

		if($val == 0)
			$record->externaccess = 0;
		else
			$record->externaccess = 1;

		$record->externcomment = 0;
		$DB->update_record("block_exaportview", $record);

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function view_grant_external_access_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function view_get_available_users_parameters() {
		return new external_function_parameters(
				array()
		);
	}

	/**
	 * Get all available users for sharing view
	 * @return all items available
	 */
	public static function view_get_available_users() {
		global $CFG,$DB,$USER;

		$mycourses = enrol_get_users_courses($USER->id, true);

		$usersincontext = array();
		foreach($mycourses as $course){
			$enrolledusers = get_enrolled_users(context_course::instance($course->id));
			foreach($enrolledusers as $user){
				if(!in_array($user, $usersincontext)){
					$usersincontext[] = $user;
				}
			}
		}

		$users = array();
		foreach($usersincontext as $user){
			$user_temp = new stdClass();
			$user_temp->id = $user->id;
			$user_temp->firstname = $user->firstname;
			$user_temp->lastname = $user->lastname;
			$users[] = $user_temp;
		}

		return $users;
	}

	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function view_get_available_users_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'id' => new external_value(PARAM_INT, 'id of user'),
								'firstname' => new external_value(PARAM_TEXT, 'firstname of user'),
								'lastname' => new external_value(PARAM_TEXT, 'lastname of user'),
						)
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function view_grant_internal_access_all_parameters() {
		return new external_function_parameters(
				array(
						'id' => new external_value(PARAM_INT, 'view id'),
						'val' => new external_value(PARAM_INT, '1 for check, 0 for uncheck')
				)
		);
	}

	/**
	 * Grant internal acces to view to all users
	 * @param int id, val
	 * @return success
	 */
	public static function view_grant_internal_access_all($id, $val) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::view_grant_internal_access_all_parameters(), array('id'=>$id, 'val'=>$val));

		$record = new stdClass();
		$record->id = $id;

		if($val == 0)
			$record->shareall = 0;
		else
			$record->shareall = 1;

		$record->externcomment = 0;
		$DB->update_record("block_exaportview", $record);

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function view_grant_internal_access_all_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function view_grant_internal_access_parameters() {
		return new external_function_parameters(
				array(
						'viewid' => new external_value(PARAM_INT, 'view id'),
						'userid' => new external_value(PARAM_INT, 'user id'),
						'val' => new external_value(PARAM_INT, '1 for check, 0 for uncheck')
				)
		);
	}

	/**
	 * Grant internal acces to view to one user
	 * @param int viewid, userid, val
	 * @return success
	 */
	public static function view_grant_internal_access($viewid, $userid, $val) {
		global $CFG,$DB,$USER;

		$params = self::validate_parameters(self::view_grant_internal_access_parameters(), array('viewid'=>$viewid, 'userid'=>$userid, 'val'=>$val));

		if($val == 1)
			$blockid = $DB->insert_record("block_exaportviewshar", array("viewid"=>$viewid, "userid"=>$userid));
		if($val == 0)
			$DB->delete_records("block_exaportviewshar", array("viewid"=>$viewid, "userid"=>$userid));

		return array("success"=>true);
	}

	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function view_grant_internal_access_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}

	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_category_parameters() {
		return new external_function_parameters(
				array(
						'categoryid' => new external_value(PARAM_INT, 'cat id')
				)
		);

	}

	/**
	 * Get views
	 * @return array of e-Portfolio views
	 */
	public static function get_category($categoryid) {
		global $CFG,$DB;

		return $DB->get_record("block_exaportcate", array("id" => $categoryid), "name");
	}

	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function get_category_returns() {
		return new external_single_structure(
				array(
						'name' => new external_value(PARAM_TEXT, 'title of category')
				)
		);
	}
	
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function delete_category_parameters() {
		return new external_function_parameters(
				array(
						'categoryid' => new external_value(PARAM_INT, 'cat id')
				)
		);
	}
	
	/**
	 * Grant internal acces to view to one user
	 * @param int viewid, userid, val
	 * @return success
	 */
	public static function delete_category($categoryid) {
		global $CFG,$DB,$USER;
	
		$params = self::validate_parameters(self::delete_category_parameters(), array('categoryid'=>$categoryid));
	
		self::block_exaport_recursive_delete_category($categoryid);
		return array("success"=>true);
	}
	
	/**
	 * Returns desription of method return values
	 * @return external_single_structure
	 */
	public static function delete_category_returns() {
		return new external_single_structure(
				array(
						'success' => new external_value(PARAM_BOOL, 'status')
				)
		);
	}
	
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_competencies_by_item_parameters() {
		return new external_function_parameters(
				array(
						'itemid' => new external_value(PARAM_INT, 'item id')
				)
		);
	}
	
	/**
	 * Get all items
	 * @return all items available
	 */
	public static function get_competencies_by_item($itemid) {
		global $CFG,$DB,$USER;
		$params = self::validate_parameters(self::get_competencies_by_item_parameters(), array('itemid'=>$itemid));
		
		return $DB->get_records("block_exacompcompactiv_mm",array("activityid"=>$itemid,"eportfolioitem"=>1, "comptype"=>TYPE_DESCRIPTOR),"","compid as competenceid");
	}
	
	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function get_competencies_by_item_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'competenceid' => new external_value(PARAM_INT, 'id of competence')
						)
				)
		);
	}
	
	/**
	 * Returns description of method parameters
	 * @return external_function_parameters
	 */
	public static function get_users_by_view_parameters() {
		return new external_function_parameters(
				array(
						'viewid' => new external_value(PARAM_INT, 'view id')
				)
		);
	}
	
	/**
	 * Get all items
	 * @return all items available
	 */
	public static function get_users_by_view($viewid) {
		global $CFG,$DB,$USER;
	
		$params = self::validate_parameters(self::get_users_by_view_parameters(), array('viewid'=>$viewid));
	
		return $DB->get_records("block_exaportviewshar",array("viewid"=>$viewid));
	}
	
	/**
	 * Returns desription of method return values
	 * @return external_multiple_structure
	 */
	public static function get_users_by_view_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'userid' => new external_value(PARAM_INT, 'id of user')
						)
				)
		);
	}
	
	private static function block_exaport_recursive_delete_category($id) {
		global $DB;
	
		// delete subcategories
		if ($entries = $DB->get_records('block_exaportcate', array("pid" => $id))) {
			foreach ($entries as $entry) {
				block_exaport_recursive_delete_category($entry->id);
			}
		}
		$DB->delete_records('block_exaportcate', array('pid'=>$id));
	
		// delete itemsharing
		if ($entries = $DB->get_records('block_exaportitem', array("categoryid" => $id))) {
			foreach ($entries as $entry) {
				$DB->delete_records('block_exaportitemshar', array('itemid'=>$entry->id));
			}
		}
			
		// delete items
		$DB->delete_records('block_exaportitem', array('categoryid'=>$id));
	}
}

?>