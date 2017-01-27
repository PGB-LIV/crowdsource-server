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

abstract class AbstractAllocator implements AllocatorInterface
{

    private $adodb;

    private $tableName;

    protected $jobId;

    public function __construct($conn, $jobId)
    {
        // TODO validate args
        $this->jobId = $jobId;
    }

    protected function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public function setWorkUnitWorker($workUnitId, $workerId)
    {
        // TODO: Validate args
        $this->adodb->Execute(
            'UPDATE `' . $tableName . '` SET `status` = \'ASSIGNED\', `assigned_to` =' . $workerId . ', `assigned_at` = NOW()
        WHERE `id` = ' . $_myWorkUnit->id . ' && `job` = ' . $this->jobId);
    }

    public abstract function getWorkUnit();

    public abstract function setWorkUnitResults();
}