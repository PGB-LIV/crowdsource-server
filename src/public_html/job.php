<?php
use pgb_liv\crowdsource\Processor\WorkUnitAllocator;

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

$myWorkUnit = NULL; // {type:'workunit', id:0, job:0, mods:=[{modtype:0,modMass:0,loc:'C'}..],$ipAddress:0, ms1:0, ms2:=[{mz:n, intensity:n}...], peptides:[{id:1, structure:"ASDFFS"}...]};

$requestType = $_GET['r'];
$workUnitAllocator = new WorkUnitAllocator($adodb, $jobId);

switch ($requestType) {
    case 'workunit':
        $myWorkUnit = $workunit->getWorkUnit();
        if ($myWorkUnit->job == 0) {
            echo 'parseResult({"type":"nomore"});'; // no more jobs this session.
            return;
        }
        
        if ($myWorkUnit->id == 0) {
            echo 'parseResult({"type":"nomore"});'; // no more jobs this session.
            return;
        }
        
        echo 'parseResult(' . json_encode($myWorkUnit) . ');';
        break;
    
    case 'terminate':
        // client has successfully said goodbye.
        break;
    
    case 'result':
        // r=result&result={"type":"result","workunit":"1","job":30,"ip":0,"peptides":[{"id":784282,"score":262055.40000000002},...]}
        $workUnitAllocator->recordResults(json_decode($_GET['result']));
        echo 'parseResult({"type":"confirmation"})';
        break;
    
    default:
        echo 'parseResult({"response":"none"});';
}
