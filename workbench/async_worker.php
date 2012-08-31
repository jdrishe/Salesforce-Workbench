<?php
declare(ticks = 1);

require_once 'shared.php';
require_once 'config/constants.php';
require_once 'config/WorkbenchConfig.php';
require_once 'context/WorkbenchContext.php';
require_once 'soxl/QueryObjects.php';
foreach (scandir('async') as $f) {
    if ($f == "." || $f == "..") continue;
    require_once "async/$f";
}

// block direct web access
if (php_sapi_name() != 'cli') {
    httpError(404, "Not Found");
}

$_SERVER['REMOTE_ADDR'] = 'CLI-' . getmypid();
$_SERVER['REQUEST_METHOD'] = 'ASYNC';

// future result gc
$frKeys = redis()->keys(FutureResult::RESULT . "*");
foreach ($frKeys as $frKey) {
    $asyncId = substr($frKey, strlen(FutureResult::RESULT));
    if (!redis()->exists(FUTURE_LOCK . $asyncId)) {
        redis()->del($frKey);
        workbenchLog(LOG_INFO, "FutureResultGC", $asyncId);
    }
}

workbenchLog(LOG_INFO, "FutureTaskQueueDepth", redis()->llen(FutureTask::QUEUE));

while (true) {
    try {
        $task = FutureTask::dequeue(30);
        pcntl_signal(SIGTERM, array($task, "handleSignal"));
        set_time_limit(WorkbenchConfig::get()->value('asyncTimeoutSeconds'));
        $task->execute();
    } catch (TimeoutException $e) {
        continue;
    }
    redis()->close();
    exit();
}
?>
