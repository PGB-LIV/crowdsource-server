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

    private $job = 0;

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

        $this->job = (int) $workUnit->getJobId();

        $phase = $this->adodb->GetOne('SELECT `phase` FROM `job_queue` WHERE `id` = ' . $this->job);
        if (is_null($phase)) {
            return false;
        }

        $allocator = null;
        switch ($phase) {
            case '1':
                $allocator = new Phase1Allocator($this->adodb, $this->job);
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
     * @return boolean|\pgb_liv\crowdsource\Core\WorkUnit The next available job or false if none are available.
     */
    public function getWorkUnit()
    {
        $job = $this->adodb->GetRow('SELECT `id`, `phase` FROM `job_queue` WHERE `state` = \'READY\' LIMIT 0,1');
        if (empty($job)) {
            return false;
        }

        $this->job = (int) $job['id'];

        $allocator = null;
        switch ($job['phase']) {
            case '1':
                $allocator = new Phase1Allocator($this->adodb, $this->job);
                break;
            default:
                return false;
        }

        $workerId = 0;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $workerId = ip2long($_SERVER['REMOTE_ADDR']);
        } else {
            // If REMOTE_ADDR is missing then either the server is configured wrong or we are in a unit test
            $workerId = ip2long('127.0.0.1');
        }

        return $allocator->getWorkUnit($workerId);
    }

    public function getJsonResponse($requestType)
    {
        switch ($requestType) {
            case 'workunit':
                return $this->getJsonResponseWorkUnit();

            case 'result':
                $this->recordResults($_GET['result']);
                return '{"type":"confirmation"}';

            default:
                return '{"response":"none"}';
        }
    }

    private function getJsonResponseWorkUnit()
    {
        $workUnit = $this->getWorkUnit();

        if ($workUnit === false) {
            return '{"type":"nomore"}';
        } else 
            if ($workUnit === true) {
                return '{"type":"retry"}';
            }

        return $workUnit->toJson();
    }

    public function getJob()
    {
        return $this->job;
    }
}
