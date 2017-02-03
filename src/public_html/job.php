<?php
use pgb_liv\crowdsource\Allocator\WorkUnitAllocator;

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

require_once '../conf/config.php';
require_once '../conf/autoload.php';
require_once '../conf/adodb.php';
require_once '../vendor/pgb-liv/php-ms/src/autoload.php';

header('Content-Type: application/json');

$requestType = 'unknown';
if (isset($_GET['r'])) {
    $requestType = $_GET['r'];
}

$workUnitAllocator = new WorkUnitAllocator($adodb);

switch ($requestType) {
    case 'workunit':
        $workUnit = $workUnitAllocator->getWorkUnit();
        
        if ($workUnit === false) {
            echo 'parseResult({"type":"nomore"});';
            return;
        }
        
        echo 'parseResult(' . json_encode($workUnit) . ');';
        break;
    
    case 'terminate':
        // client has successfully said goodbye.
        break;
    
    case 'result':
        $workUnitAllocator->recordResults(json_decode($_GET['result']));
        echo 'parseResult({"type":"confirmation"})';
        break;
    
    default:
        echo 'parseResult({"response":"none"});';
}
