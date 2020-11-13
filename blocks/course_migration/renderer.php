<?php

class block_summary_renderer extends plugin_renderer_base {

	
	public function navigation_tree($model) {
		echo 'render';
		/*
		$navigation->add_class('navigation_node');
		$content = $this->navigation_node(array($navigation), array('class'=>'block_tree list'), $expansionlimit, $options);
		if (isset($navigation->id) && !is_numeric($navigation->id) && !empty($content)) {
			$content = $this->output->box($content, 'block_tree_box', $navigation->id);
		}
		*/
		return $content;
	}
}