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
    public function recordResults($results)
    {
        $job = $this->adodb->GetRow('SELECT `id`, `phase` FROM `job_queue` WHERE `id` = ' . $this->adodb->quote($results->job));
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
        
        $allocator->setWorkUnitResults($results);
    }

    /**
     * Gets the next available work unit for the requester.
     *
     * @return boolean|\pgb_liv\crowdsource\Allocator\The The next available job or false if none are available.
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
            $allocator->setWorkUnitWorker((int) $workUnit->id, ip2long($_SERVER['REMOTE_ADDR']));
        }
        
        return $workUnit;
    }
}
