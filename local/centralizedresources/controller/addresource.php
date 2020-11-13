<?php
//require_once('../../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/local/centralizedresources/form/addResource_form.php');
require_once($CFG->dirroot . '/local/centralizedresources/lib/cr_file_api.php');
require_once($CFG->dirroot . '/local/centralizedresources/lib/libffmpeg.php');
require_once($CFG->dirroot . '/local/centralizedresources/lib/libthumbnail.php');
require_once($CFG->dirroot . '/local/centralizedresources/lib/cr_insert_resource.php');
require_once($CFG->dirroot . '/local/mr/framework/controller.php');

class local_centralizedresources_controller_addresource extends mr_controller {
	
	public function default_action()
	{
		global $PAGE, $CFG, $USER, $COURSE;
		
		$context = $this->get_context();
		$view = '';
		
		if(has_capability('local/centralizedresources:addresource', $context))
		{
			$addResourceForm = new addResource_form($PAGE->url);
			
			if($addResourceForm->is_submitted())
			{
				$data = $addResourceForm->get_submitted_data();

				if(isset($data->cr_cancel) && $data->cr_cancel){
					redirect($CFG->wwwroot.'/local/centralizedresources/view.php?controller=manageresource&action=default&courseid='.$data->course);
				}
				
				if($addResourceForm->is_validated()){
					$fileinfo = cr_moveFileToMediaFolder('attachments', $data->type);
					
					$data->filename = $fileinfo['filename'];
					$data->hash = $fileinfo['hashname'];
					$data->extension = $fileinfo['extension'];
					$data->type = $fileinfo['type'];
					$data->filesize = $fileinfo['filesize'];
					$data->cleanname =$fileinfo['cleanname'];
					$data->createDate = $fileinfo['createDate'];
					$data->lastusedate = $fileinfo['createDate'];
					$data->editdate = $fileinfo['createDate'];
					$data->mimetype = $fileinfo['mimetype'];
					
					$data->resourceid = sha1($data->hash . $data->createDate);

                    if(!isset($data->domainrestricted)){
                        $data->domainrestricted = 0;
                    }

					cr_insertResource($data);
					
					$msg = get_string('local_cr_add_resource_validation_text', 'local_centralizedresources');
					
					
					if(isset($data->cr_save_return) && $data->cr_save_return)
					{
						redirect($CFG->wwwroot.'/course/view.php?id='.$data->course, "<p>".$msg."</p>");
					}
					
					if(isset($data->cr_save) && $data->cr_save)
					{
						redirect($CFG->wwwroot.'/local/centralizedresources/view.php?controller=manageresource&action=default&courseid='.$data->course, "<p>".$msg."</p>");
					}
				}
			}
			
			//Display the form
			ob_start();
			
			$addResourceForm->display();
			
			$view = ob_get_contents();
			
			ob_end_clean();
		}
		
		return $view;

	}
}