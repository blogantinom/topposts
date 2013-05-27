<?php
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

function task_topposts($task)
{
    require_once MYBB_ROOT.'inc/plugins/topposts.php';
    global $mybb, $lang;

    if (!$lang->topposts) {
        $lang->load('topposts');
    }

    if (tp_UpdateContent()) {
        add_task_log($task, $lang->topposts_task_update_topposts);
    } else {
        add_task_log($task, $lang->topposts_task_didnt_update_topposts);
    }
} 