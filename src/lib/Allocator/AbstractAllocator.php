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

abstract class AbstractAllocator implements AllocatorInterface
{

    protected $adodb;

    private $tableName;

    protected $jobId;

    /**
     * Creates a new instance of a job allocator.
     *
     * @param \ADOConnection $conn
     *            A valid and connected ADOdb instance
     * @param int $jobId
     *            The job to preprocess
     * @throws \InvalidArgumentException If job is not an integer
     */
    public function __construct(\ADOConnection $conn, $jobId)
    {
        if (! is_int($jobId)) {
            throw new \InvalidArgumentException('Job ID must be an integer value. Valued passed is of type ' . gettype($jobId));
        }
        
        $this->adodb = $conn;
        $this->jobId = $jobId;
    }

    protected function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \pgb_liv\crowdsource\Allocator\AllocatorInterface::setWorkUnitWorker()
     */
    public function setWorkUnitWorker($workUnitId, $workerId)
    {
        if (! is_int($workUnitId)) {
            throw new \InvalidArgumentException('Argument 1 must be an integer value. Valued passed is of type ' . gettype($workUnitId));
        }
        
        if (! is_int($workUnitId)) {
            throw new \InvalidArgumentException('Argument 2 must be an integer value. Valued passed is of type ' . gettype($workerId));
        }
        
        $this->adodb->Execute(
            'UPDATE `' . $this->tableName . '` SET `status` = \'ASSIGNED\', `assigned_to` =' . $workerId . ', `assigned_at` = NOW()
        WHERE `id` = ' . $workUnitId . ' && `job` = ' . $this->jobId);
    }

    abstract public function getWorkUnit();

    abstract public function setWorkUnitResults($results);
}
