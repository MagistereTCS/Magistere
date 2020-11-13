<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once($CFG->dirroot.'/local/magisterelib/MagistereMonitoring.php');

$task = optional_param('task', 0, PARAM_INT);
$ignore = optional_param('ignore', false, PARAM_BOOL);

require_login();
if ($USER->id != 2)
{
    redirect(new moodle_url());
    die;
}

if ($task > 0)
{
    $ignore = ($ignore == 1 || $ignore == true);
    $magmon = new MagistereMonitoring();
    $magmon->setTaskIgnore($task,$ignore);
    echo '{"error":false}';
    die;
}

$PAGE->set_url('/local/magisterelib/monitoringAdmin.php');
$PAGE->set_course($SITE);
$PAGE->navbar->add('Monitoring admin');
// Prevent caching of this page to stop confusion when changing page after making AJAX changes
$PAGE->set_cacheable(false);
$PAGE->requires->jquery();

echo $OUTPUT->header();


$magmon = new MagistereMonitoring();

$tasksmon = $magmon->getMonitoredTasks();

echo '<style>

.ok {
  background-color: #50f765;
}
.warning {
  background-color: #f7cb50;
}
.failed {
  background-color: #f75850;
}
.unknown {
  background-color: #999999;
}
.ignored {
  background-color: #999999;
}

</style>';





echo '<table border="1">';

echo '<tr><th>academy</th><th>classname</th><th>status</th><th>faildelay</th><th>nextrun</th><th>lastupdate</th><th>ignoretask</th></tr>';

foreach($tasksmon AS $taskmon)
{
    if ($taskmon->status == 0 || $taskmon->ignoretask == 1){continue;}
    $status = 'unknown';
    switch ($taskmon->status)
    {
        case 0:
            $status = 'ok';
            break;
        case 1:
            $status = 'warning';
            break;
        case 2:
            $status = 'failed';
    }
    echo '<tr><td>'.$taskmon->academy.'</td><td>'.$taskmon->classname.'</td><td class="'.$status.'">'.$status.' ('.$taskmon->status.')</td><td>'.$taskmon->faildelay.'</td><td>'.$taskmon->nextrun.'</td><td>'.$taskmon->lastupdate.'</td><td><input class="ignoretaskcb" name="'.$taskmon->id.'" type="checkbox"'.($taskmon->ignoretask==0?'':' checked="checked"').'></td></tr>';
}

echo '</table><br/><br/><br/>';

echo '<table border="1">';

echo '<tr><th>academy</th><th>classname</th><th>status</th><th>faildelay</th><th>nextrun</th><th>lastupdate</th><th>ignoretask</th></tr>';

foreach($tasksmon AS $taskmon)
{
    $status = 'unknown';
    if ($taskmon->ignoretask == 0)
    {
        switch ($taskmon->status)
        {
            case 0:
                $status = 'ok';
                break;
            case 1:
                $status = 'warning';
                break;
            case 2:
                $status = 'failed';
        }
    }else{
        $status = 'ignored';
    }
    echo '<tr><td>'.$taskmon->academy.'</td><td>'.$taskmon->classname.'</td><td class="'.$status.'">'.$status.' ('.$taskmon->status.')</td><td>'.$taskmon->faildelay.'</td><td>'.$taskmon->nextrun.'</td><td>'.$taskmon->lastupdate.'</td><td><input class="ignoretaskcb" type="checkbox"'.($taskmon->ignoretask==0?'':' checked="checked"').'></td></tr>';
}

echo '</table>';

echo '<script>

$(document).ready(function () {
    console.log("Ready ...");
    $(".ignoretaskcb").change(function () {
        var name = $(this).attr("name");
        var check = $(this).prop("checked");
        $.ajax({
          type: "POST",
          url: "monitoringAdmin.php",
          data: "task="+name+"&ignore="+check,
          success: function(data){console.log(data);},
          dataType: "json"
        });
    });
});

</script>';




echo $OUTPUT->footer();












