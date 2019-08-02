<?php
/**
 * Copyright 2019 University of Liverpool
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
use pgb_liv\crowdsource\Preprocessor\Phase1Preprocessor;
use pgb_liv\crowdsource\Postprocessor\Phase1Postprocessor;
use pgb_liv\crowdsource\Parallel\Master\WorkUnitMaster;

ini_set('memory_limit', '8G');

error_reporting(E_ALL);
ini_set('display_errors', true);

chdir(__DIR__);

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/autoload.php';

$lockDir = DATA_PATH . '/.lock';

$lockCount = 2;
$lockFilePath = $lockDir . '/.PreprocessorLock';
$jidFilePath = $lockDir . '/.PreprocessorJid';

$availableLock = null;
// Init
$jobsRunning = array(
    0
);
for ($lockIndex = 1; $lockIndex <= $lockCount; $lockIndex ++) {
    $lockFile = $lockFilePath . $lockIndex;
    if (! file_exists($lockFile)) {
        if (! is_dir($lockDir)) {
            mkdir($lockDir);
        }

        touch($lockFile);
    }

    if (! file_exists($jidFilePath . $lockIndex)) {
        touch($jidFilePath . $lockIndex);
    }

    $lock = fopen($lockFile, 'w+');

    if (! flock($lock, LOCK_EX | LOCK_NB)) {
        $jobId = file_get_contents($jidFilePath . $lockIndex);

        if (strlen($jobId) > 0) {
            $jobsRunning[] = $jobId;
        }
    } else {
        $availableLock = $lockIndex;
    }

    fclose($lock);
}

if (is_null($availableLock)) {
    die('Terminating: All processors running');
}

echo '[' . date('r') . '] Locking ' . $lockFilePath . $availableLock . PHP_EOL;

$lock = fopen($lockFilePath . $availableLock, 'w+');

if (! flock($lock, LOCK_EX | LOCK_NB)) {
    die('Terminating: Available lock locked');
}
// Got lock

echo '[' . date('r') . '] Starting preprocessor.' . PHP_EOL;

$job = $adodb->GetRow(
    'SELECT `id`, `state` FROM `job_queue` WHERE `state` != \'COMPLETE\' && `id` NOT IN(' . implode(',', $jobsRunning) .
    ') ORDER BY `created_at` ASC');

if (empty($job)) {
    die('[' . date('r') . '] Terminating. No jobs awaiting processing.' . PHP_EOL);
}

$state = $job['state'];
$jobId = (int) $job['id'];

if (empty($job)) {
    die('[' . date('r') . '] Terminating. Nothing to do.' . PHP_EOL);
}

file_put_contents($jidFilePath . $availableLock, $jobId);

// var_dump($job);
// exit();

echo '[' . date('r') . '] Found job: ' . $job['id'] . ' - Phase: ' . $state . '.' . PHP_EOL;

// Either new or has failed
switch ($state) {
    case 'NEW':
    case 'FASTA':
    case 'RAW':
        echo '[' . date('r') . '] Indexing Job.' . PHP_EOL;
        $phase1 = new Phase1Preprocessor($adodb, $jobId);
        $phase1->process();
    case 'INDEXED':
    case 'WORKUNITS':
        // TODO: Purge queues
        echo '[' . date('r') . '] Preparing Workunits.' . PHP_EOL;
        $master = new WorkUnitMaster($adodb, $jobId);
        $master->processJobs();
    case 'PROCESSING':

        $phase1 = new Phase1Postprocessor($adodb, $jobId);
        
        echo '[' . date('r') . '] Waiting for Results.' . PHP_EOL;
        do {
            sleep(1);
        } while (! $phase1->resultsReady());
        
        echo '[' . date('r') . '] Processing Results.' . PHP_EOL;
        if ($phase1->resultsReady()) {
            echo '[' . date('r') . '] Generating results.' . PHP_EOL;
            $phase1->generateResults();

            echo '[' . date('r') . '] Purging temporary data.' . PHP_EOL;
            $phase1->clean();

            echo '[' . date('r') . '] Marking job complete.' . PHP_EOL;
            $phase1->finalise();
        }
        break;
    default:
        echo '[' . date('r') . '] Unknown state: ' . $state . PHP_EOL;
}

echo '[' . date('r') . '] Done.' . PHP_EOL;

file_put_contents($jidFilePath . $availableLock, '');
ftruncate($lock, 0);
fclose($lock);