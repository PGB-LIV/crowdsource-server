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
namespace pgb_liv\crowdsource\Allocator;

use pgb_liv\crowdsource\Core\WorkUnit;
use pgb_liv\crowdsource\Core\Phase1WorkUnit;

class WorkUnitAllocator
{

    private $adodb;

    public function __construct(\ADOConnection $conn)
    {
        $this->adodb = $conn;
    }

    /**
     * Records an array of results to the database
     *
     * @param array $results
     *            An array of WorkUnit's with scores
     */
    public function recordResults($jsonStr)
    {
        $workUnit = WorkUnit::fromJson($jsonStr);
        
        $phase = $this->adodb->GetOne('SELECT `phase` FROM `job_queue` WHERE `id` = ' . $this->adodb->quote($workUnit->getJobId()));
        if (is_null($phase)) {
            return false;
        }
        
        $allocator = null;
        switch ($phase) {
            case '1':
                $allocator = new Phase1Allocator($this->adodb, $workUnit->getJobId());
                break;
            case '2':
                $allocator = new Phase2Allocator($this->adodb, $workUnit->getJobId());
                break;
            case '3':
                $allocator = new Phase3Allocator($this->adodb, $workUnit->getJobId());
                break;
            default:
                return false;
        }
        
        $allocator->setWorkUnitResults($workUnit);
        return true;
    }

    /**
     * Gets the next available work unit for the requester.
     *
     * @return boolean|\pgb_liv\crowdsource\Core\Phase1WorkUnit The next available job or false if none are available.
     */
    public function getWorkUnit()
    {
        $job = $this->adodb->GetRow('SELECT `id`, `phase` FROM `job_queue` WHERE `state` = \'READY\' LIMIT 0,1');
        if (empty($job)) {
            return false;
        }
        
        $allocator = null;
        switch ($job['phase']) {
            case '1':
                $allocator = new Phase1Allocator($this->adodb, (int) $job['id']);
                break;
            case '2':
                $allocator = new Phase2Allocator($this->adodb, (int) $job['id']);
                break;
            case '3':
                $allocator = new Phase3Allocator($this->adodb, (int) $job['id']);
                break;
            default:
                return false;
        }
        
        $workUnit = $allocator->getWorkUnit();
        // If false, no job was available for allocation
        if ($workUnit !== false) {
            $workerId = 0;
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $workerId = ip2long($_SERVER['REMOTE_ADDR']);
            } else {
                // If REMOTE_ADDR is missing then either the server is configured wrong or we are in a unit test
                $workerId = ip2long('127.0.0.1');
            }
            
            $allocator->setWorkUnitWorker($workerId, $workUnit);
        }
        
        return $workUnit;
    }

    public function getJsonResponse($requestType)
    {
        switch ($requestType) {
            case 'workunit':
                $workUnit = $this->getWorkUnit();
                
                if ($workUnit === false) {
                    return 'parseResult({"type":"nomore"});';
                }
                
                return 'parseResult(' . $workUnit->toJson() . ');';
            
            case 'result':
                $workUnitAllocator->recordResults($_GET['result']);
                return 'parseResult({"type":"confirmation"})';
            
            default:
                return 'parseResult({"response":"none"});';
        }
    }
}
