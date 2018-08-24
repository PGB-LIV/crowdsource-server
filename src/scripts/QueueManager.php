<?php
/**
 * Copyright 2016 University of Liverpool
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
error_reporting(E_ALL);
ini_set('display_errors', true);

chdir(__DIR__);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

$lockDir = DATA_PATH . '/.lock';
$lockFile = $lockDir . '/.QueueManagerLock';

if (! file_exists($lockFile)) {
    if (! is_dir($lockDir)) {
        mkdir($lockDir);
    }

    touch($lockFile);
}

$lock = fopen($lockFile, 'r+');

if (! flock($lock, LOCK_EX | LOCK_NB)) {
    die('Process Running. Terminating.');
}

echo '[' . date('r') . '] Starting deamon.' . PHP_EOL;

$queue = null;
$jobId = null;
$sleepTime = 1;
$attempts = 1;
$idle = false;
$precursorQueue = array();
do {
    // 2 minute maximum idle
    if ($sleepTime < 120) {
        if ($idle) {
            $attempts ++;
        }
    }

    $sleepTime = pow($attempts, 2);
    sleep($sleepTime);

    if (is_null($queue)) {

        if (! msg_queue_exists(MESSAGE_QUEUE)) {
            // BugFix. For some reason we can't create the queue, the allocator must or else it can't access it?!
            echo '[' . date('r') . '] Idling. Waiting for allocator to create queue.' . PHP_EOL;
            $idle = true;
            continue;
        }

        $queue = msg_get_queue(MESSAGE_QUEUE);
    }

    $stat = msg_stat_queue($queue);

    // Too many jobs in queue
    if ($stat['msg_qnum'] > 1000) {
        echo '[' . date('r') . '] Idling. Queue full' . PHP_EOL;
        $idle = true;
        continue;
    }

    // Select Job
    if (is_null($jobId)) {
        $jobId = $adodb->GetOne('SELECT `id` FROM `job_queue` WHERE `state` = "READY"');

        // No jobs ready
        if (is_null($jobId)) {
            echo '[' . date('r') . '] Idling. No jobs.' . PHP_EOL;
            $idle = true;
            continue;
        }

        echo '[' . date('r') . '] Found job ' . $jobId . '.' . PHP_EOL;
    }

    // Have job and queue has space

    // Local queue empty
    if (count($precursorQueue) == 0) {
        echo '[' . date('r') . '] RAM queue empty.' . PHP_EOL;
        $precursorQueue = $adodb->GetCol(
            'SELECT DISTINCT `precursor` FROM `workunit1` WHERE `job` = ' . $jobId .
            ' && (`status` = "UNASSIGNED" || (`status` = "ASSIGNED" && UNIX_TIMESTAMP()-UNIX_TIMESTAMP(`assigned_at`) > 30))');

        // No new work, job done?
        if (count($precursorQueue) == 0) {
            echo '[' . date('r') . '] MySQL queue empty.' . PHP_EOL;
            $jobsRemaining = $adodb->GetOne(
                'SELECT COUNT(DISTINCT `precursor`) FROM `workunit1` WHERE `job` = ' . $jobId .
                ' && `status` != "COMPLETE"');

            if ($jobsRemaining == 0) {
                echo '[' . date('r') . '] Job complete.' . PHP_EOL;
                $adodb->Execute('UPDATE `job_queue` SET `state` = "DONE" WHERE `id` = ' . $jobId);
                $jobId = null;
                continue;
            } else {
                // Waiting for assigned jobs to finish
                $idle = true;
                continue;
            }
        }
    }

    $queueSize = $stat['msg_qnum'];

    echo '[' . date('r') . '] Filling Queue.' . PHP_EOL;
    foreach ($precursorQueue as $idx => $precursor) {
        unset($precursorQueue[$idx]);
        $bool = msg_send($queue, $jobId, $precursor, true, false, $errorCode);

        if (! $bool || $queueSize > 1500) {
            break;
        }

        $queueSize ++;
    }

    // Reset idle
    $idle = false;
    $attempts = 1;
} while (true);
