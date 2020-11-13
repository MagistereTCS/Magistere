<?php

class MessageOutput {

    const WARNING = 'warning';

    const SUCCESS = 'success';

    private $mode;

    private $cls;

    public function __construct($mode)
    {
        $this->mode = $mode;



        if($mode == self::SUCCESS){
            $this->cls = 'alert alert-success alert-box';
        }

        if($mode == self::WARNING){
            $this->cls = 'alert alert-warning alert-box';
        }
    }

    public function start_display($customcls = '')
    {
        global $OUTPUT;

        echo $OUTPUT->box_start($this->cls.$customcls);
    }

    public function display($msg, $breakline = true){
        echo $msg;

        if($breakline){
            echo "\n<br/>";
        }
    }

    public function end_display()
    {
        global $OUTPUT;

        echo $OUTPUT->box_end();
    }
}